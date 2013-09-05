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

    public function getMethodSwitcher($original_code, $static)
    {
        return sprintf(
            'if (%s) { return %s } else { %s }',
            $this->getMethodBehaviorCondition($static),
            $this->getMethodReturnCode($static),
            $original_code
        );
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
        \$this->delusion_invokes = [];
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
            if ($method->isAbstract()) {
                continue;
            }
            $transformed_method = $this->methodInjector($method);
            $code = str_replace($method->getSource(), $transformed_method, $code);
        }

        return $code;
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
        $offset = (($method->getDocComment()) ? strlen($method->getDocComment()) : 0);
        $start_position = strpos($source, '{', $offset) + 1;
        $end_position = strrpos($source, '}');
        $original_code = substr($source, $start_position, $end_position - $start_position);

        $code = PHP_EOL . $this->getMethodDelusionClass();
        $code .= $this->getMethodIncreaseInvokes($method->isStatic()) . PHP_EOL;
        if ($method->isConstructor() || $method->isDestructor()) {
            $code .= sprintf('if (!%s) { %s }', $this->getMethodBehaviorCondition(true), $original_code);
        } else {
            if (!$method->isStatic()) {
                $original_code = $this->getMethodSwitcher($original_code, true);
            }
            $code .= $this->getMethodSwitcher($original_code, $method->isStatic());

        }

        return substr($source, 0, $start_position) . $code . substr($source, $end_position);
    }

    /**
     * Get code which adds a variable with class behavior.
     *
     * @return string
     */
    private function getMethodDelusionClass()
    {
        $definition = '$___delusion = \Delusion\Delusion::injection();' . PHP_EOL;

        return $definition . '$___delusion_class = $___delusion->getClassBehavior(__CLASS__);' . PHP_EOL;
    }

    /**
     * Get code which uses in condition of what value will be returned.
     *
     * @param bool $static
     *
     * @return string
     */
    private function getMethodBehaviorCondition($static = false)
    {
        if ($static) {
            return '$___delusion_class->delusionHasCustomBehavior(__FUNCTION__)';
        } else {
            return 'array_key_exists(__FUNCTION__, $this->delusion_returns)';
        }
    }

    /**
     * Get code which increases counter of method invokes.
     *
     * @param bool $static
     *
     * @return string
     */
    private function getMethodIncreaseInvokes($static = false)
    {
        if ($static) {
            return '$___delusion_class->registerInvoke(__FUNCTION__, func_get_args());';
        } else {
            return <<<END
        if (empty(\$this->delusion_invokes[__FUNCTION__])) {
            \$this->delusion_invokes[__FUNCTION__] = [];
        }
        array_push(\$this->delusion_invokes[__FUNCTION__], func_get_args());
END;
        }
    }

    /**
     * Get code which returns custom value.
     *
     * @param bool $static
     *
     * @return string
     */
    private function getMethodReturnCode($static = false)
    {
        if ($static) {
            $return_code = '$___delusion_return = $___delusion_class->getCustomBehavior(__FUNCTION__);';
        } else {
            $return_code = '$___delusion_return = $this->delusion_returns[__FUNCTION__];';
        }
        $return_code .= <<<END

            if (is_callable(\$___delusion_return)) {
                return \$___delusion_return(func_get_args());
            } else {
                return \$___delusion_return;
            }
END;

        return $return_code;
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
