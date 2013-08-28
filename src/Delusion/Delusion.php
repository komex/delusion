<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use Composer\Autoload\ClassLoader;
use TokenReflection\Broker;
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
            $bucket->data = $this->spoof();
            $consumed += strlen($bucket->data);
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Get behavior of static class.
     *
     * @param string $class
     *
     * @return PuppetThreadInterface
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
        if ($class->isInterface()) {
            return $class->getSource();
        } elseif ($class->isTrait()) {
            // @todo: Traits support.
            return $class->getSource();
        } else {
            $code = $class->getFileReflection()->getSource();
            $regexp = sprintf(
                '/\bclass\s+%s(?:\s+(?:implements|extends)\s+[\w\\\\]+)*/im',
                quotemeta(self::$instance->current_class)
            );
            $corrected = 'class ' . self::$instance->current_class;
            if ($class->getParentClassName() != '') {
                $corrected .= ' extends ' . $class->getParentClassName();
            }
            $interfaces = $class->getOwnInterfaceNames();
            array_push($interfaces, '\\Delusion\\PuppetThreadInterface');
            $interfaces = join(', ', array_unique($interfaces));
            $corrected .= ' implements ' . $interfaces;
            $code = preg_replace($regexp, $corrected, $code, 1);
            /** @var ReflectionMethod[] $methods */
            $methods = $class->getOwnMethods();
            $injected_code = <<<END
    protected \$delusion_invokes = [];
    protected \$delusion_returns = [];

    public function delusionGetInvokesCount(\$method)
    {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }

    public function delusionGetInvokesArguments(\$method)
    {
        return array_key_exists(\$method, \$this->delusionInvokes) ? \$this->delusionInvokes[\$method] : [];
    }

    public function delusionSetBehavior(\$method, \$returns)
    {
        \$this->delusionReturns[\$method] = \$returns;
    }

    public function delusionResetBehavior(\$method)
    {
        unset(\$this->delusionReturns[\$method]);
    }

    public function delusionHasCustomBehavior(\$method)
    {
        return array_key_exists(\$method, \$this->delusionReturns);
    }

END;
            $position = strpos($code, $methods[0]->getSource());
            $code = substr($code, 0, $position) . $injected_code . substr($code, $position);

            foreach ($methods as $method) {
                if ($method->isConstructor()) {
                    // @todo Constructor control
                    $transformed_method = $method->getSource();
                } elseif ($method->isDestructor()) {
                    // @todo Destructor control
                    $transformed_method = $method->getSource();
                } elseif ($method->isAbstract()) {
                    $transformed_method = $method->getSource();
                } else {
                    $transformed_method = $this->methodInjector($method);
                }
                $code = str_replace($method->getSource(), $transformed_method, $code);
            }

            return $code;
        }
    }

    /**
     * Transform particular method.
     *
     * @param ReflectionMethod $method
     *
     * @return string
     */
    private function methodInjector(ReflectionMethod $method)
    {
        $source = $method->getSource();
        $start_position = strpos($source, '{') + 1;
        $end_position = strrpos($source, '}');
        $original_code = substr($source, $start_position, $end_position - $start_position);

        if ($method->isStatic()) {
            $code = <<<END

        \$delusion = \Delusion\Delusion::injection();
        \$class = \$delusion->getClassBehavior(__CLASS__);
        \$class->registerInvoke(__FUNCTION__, func_get_args());
        if (\$class->delusionHasCustomBehavior(__FUNCTION__) !== null) {
            \$return = \$class->getCustomBehavior(__FUNCTION__);
            if (is_callable(\$return)) {
                return \$return(func_get_args());
            } else {
                return \$return;
            }
        } else {
END;
        } else {
            $code = <<<END

        if (empty(\$this->delusionInvokes[__FUNCTION__])) {
            \$this->delusionInvokes[__FUNCTION__] = [];
        }
        array_push(\$this->delusionInvokes[__FUNCTION__], func_get_args());
        if (array_key_exists(__FUNCTION__, \$this->delusionReturns)) {
            return \$this->delusionReturns[__FUNCTION__];
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
                $this->broker->processFile($file);
                $this->current_class = $class;
                include('php://filter/read=delusion.loader/resource=' . $file);
            }

            return;
        }
        $this->composer->loadClass($class);
    }
}
