<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Composer\Autoload\ClassLoader;
use TokenReflection\Broker;
use TokenReflection\ReflectionClass;
use TokenReflection\ReflectionMethod;

/**
 * Class Delusion
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Delusion extends \php_user_filter
{
    /**
     * @var Delusion
     */
    private static $instance;
    /**
     * @var Broker
     */
    private $broker;
    /**
     * @var ClassLoader
     */
    private $composer;
    /**
     * @var string
     */
    private $current_class;
    /**
     * @var ClassBehavior[]
     */
    private $static_classes = [];

    /**
     * Init Delusion.
     *
     * @throws \RuntimeException
     */
    private function __construct()
    {
        $autoloaders = spl_autoload_functions();
        $this->composer = $this->findComposer($autoloaders);
        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->broker = new Broker(new Broker\Backend\Memory());
        stream_filter_register('delusion.loader', __CLASS__);
    }

    /**
     * Starts perform magic.
     *
     * @return Delusion
     */
    public static function injection()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Spoof class.
     *
     * @param resource $in
     * @param resource $out
     * @param int $consumed
     * @param bool $closing
     *
     * @return int|void
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        /** @var resource|object $bucket */
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (self::$instance->current_class !== null) {
                $bucket->data = $this->spoof();
                self::$instance->current_class = null;
                $consumed += strlen($bucket->data);
                stream_bucket_append($out, $bucket);
            }
        }

        return PSFS_PASS_ON;
    }

    /**
     * Get behavior of static class.
     *
     * @param string $class
     *
     * @return ClassBehavior
     * @throws \InvalidArgumentException If class does not loaded.
     */
    public function getClassBehavior($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        if (empty($this->static_classes[$class])) {
            $this->static_classes[$class] = new ClassBehavior($this->broker->getClass($class));
        }

        return $this->static_classes[$class];
    }

    /**
     * Find composer ClassLoader and unregister its autoload.
     *
     * @param array $loaders
     *
     * @return ClassLoader|null
     */
    private function findComposer(array $loaders)
    {
        foreach ($loaders as $loader) {
            if ($loader[0] instanceof ClassLoader) {
                spl_autoload_unregister($loader);

                return $loader[0];
            }
        }

        return null;
    }

    /**
     * Transform original class for getting full control.
     *
     * @return string
     */
    private function spoof()
    {
        $class = self::$instance->broker->getClass(self::$instance->current_class);
        $code = $class->getFileReflection()->getSource();
        if ($class->isInterface()) {
            return $code;
        } else {
            /** @var ReflectionMethod[] $methods */
            $methods = $class->getOwnMethods();
            if (!empty($methods)) {
                if (!$class->isTrait()) {
                    $code = $this->addDelusionInterface($code, $class);
                    $code = $this->addDelusionMethods($code, $methods[0]);
                }
                $code = $this->replaceMethods($code, $methods);
            }

            return $code;
        }
    }

    /**
     * Modified class must implements Delusion interface.
     *
     * @param string $code
     * @param ReflectionClass $class
     *
     * @return string
     */
    private function addDelusionInterface($code, ReflectionClass $class)
    {
        $regexp = sprintf(
            '/\bclass\s+%s(?:\s+(?:implements|extends)\s+[\w_\\,\s]+)?\s*{/im',
            quotemeta(self::$instance->current_class)
        );
        $corrected = 'class ' . self::$instance->current_class;
        if ($class->getParentClassName() != '') {
            $corrected .= ' extends ' . $class->getParentClassName();
        }
        $interfaces = $class->getOwnInterfaceNames();
        array_push($interfaces, '\\Delusion\\PuppetThreadInterface');
        $interfaces = join(', ', array_unique($interfaces));
        $corrected .= ' implements ' . $interfaces . ' {';

        return preg_replace($regexp, $corrected, $code, 1);
    }

    /**
     * Add methods for working with Delusion.
     *
     * @param string $code
     * @param ReflectionMethod $method First method definition
     *
     * @return string
     */
    private function addDelusionMethods($code, ReflectionMethod $method)
    {
        $injected_code = <<<END
    protected \$delusion_invokes = [];
    protected \$delusion_returns = [];

    public function delusionGetInvokesCount(\$method)
    {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }

    public function delusionGetInvokesArguments(\$method)
    {
        return array_key_exists(\$method, \$this->delusion_invokes) ? \$this->delusion_invokes[\$method] : [];
    }

    public function delusionSetBehavior(\$method, \$returns)
    {
        \$this->delusion_returns[\$method] = \$returns;
    }

    public function delusionResetBehavior(\$method)
    {
        unset(\$this->delusion_returns[\$method]);
    }

    public function delusionHasCustomBehavior(\$method)
    {
        return array_key_exists(\$method, \$this->delusion_returns);
    }

    public function delusionResetAllBehavior()
    {
        \$this->delusion_returns = [];
    }

    public function delusionResetInvokesCounter(\$method)
    {
        unset(\$this->delusion_invokes[\$method]);
    }

    public function delusionResetAllInvokesCounter()
    {
        unset(\$this->delusion_invokes);
    }

END;
        $position = strpos($code, $method->getSource());

        return substr($code, 0, $position) . $injected_code . substr($code, $position);
    }

    /**
     * Replace methods in file to modified.
     *
     * @param string $code
     * @param ReflectionMethod[] $methods
     *
     * @return string
     */
    private function replaceMethods($code, array $methods)
    {
        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                $transformed_method = $this->methodInjector($method, false);
            } elseif ($method->isAbstract()) {
                $transformed_method = $method->getSource();
            } else {
                $transformed_method = $this->methodInjector($method);
            }
            $code = str_replace($method->getSource(), $transformed_method, $code);
        }

        return $code;
    }

    /**
     * Transform particular method.
     *
     * @param ReflectionMethod $method
     * @param bool $return Will method return something (needs for constructor)?
     *
     * @return string
     */
    private function methodInjector(ReflectionMethod $method, $return = true)
    {
        $source = $method->getSource();
        $start_position = strpos($source, '{') + 1;
        $end_position = strrpos($source, '}');
        $original_code = substr($source, $start_position, $end_position - $start_position);
        $return_code = '';
        if ($method->isStatic()) {
            if ($return) {
                $return_code = <<<END

            \$return = \$class->getCustomBehavior(__FUNCTION__);
            if (is_callable(\$return)) {
                return \$return(func_get_args());
            } else {
                return \$return;
            }
END;
            }
            $code = <<<END

        \$delusion = \Delusion\Delusion::injection();
        \$class = \$delusion->getClassBehavior(__CLASS__);
        \$class->registerInvoke(__FUNCTION__, func_get_args());
        if (\$class->delusionHasCustomBehavior(__FUNCTION__)) {
            $return_code
        } else {
END;
        } else {
            $return_code = $return ? 'return $this->delusion_returns[__FUNCTION__];' : '';
            $code = <<<END

        if (empty(\$this->delusion_invokes[__FUNCTION__])) {
            \$this->delusion_invokes[__FUNCTION__] = [];
        }
        array_push(\$this->delusion_invokes[__FUNCTION__], func_get_args());
        if (array_key_exists(__FUNCTION__, \$this->delusion_returns)) {
            $return_code
        } else {
END;
        }

        return substr($source, 0, $start_position) . $code . $original_code . '}' . substr($source, $end_position);
    }

    /**
     * Load class by its name.
     *
     * @param string $class
     */
    private function loadClass($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        $prefix = explode('\\', $class, 2)[0];
        if (!in_array($prefix, ['TokenReflection', 'Delusion'])) {
            if (!$this->broker->hasClass($class)) {
                $file = $this->composer->findFile($class);
                if (empty($file)) {
                    return;
                }
                $this->broker->processFile($file);
                $this->current_class = $class;
                include('php://filter/read=delusion.loader/resource=' . $file);
            }

            return;
        }
        $this->composer->loadClass($class);
    }
}
