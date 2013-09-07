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
     * Loads class if it's not in the black list.
     */
    const STRATEGY_ALLOW = 1;
    /**
     * Loads class if it's in a white list.
     */
    const STRATEGY_DENY = 2;
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
     * @var int
     */
    private $strategy = self::STRATEGY_ALLOW;
    /**
     * @var array
     */
    private $white_list = [];
    /**
     * @var array
     */
    private $black_list = ['Delusion', 'TokenReflection'];
    /**
     * @var string
     */
    private $prefix;

    /**
     * Init Delusion.
     *
     * @throws \RuntimeException
     */
    private function __construct()
    {
        $this->prefix = sprintf('___delusion_%s___', substr(sha1(rand()), 0, 5));
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
     * Get list of classes which will not be transformed.
     *
     * @return array
     */
    public function getBlackList()
    {
        return $this->black_list;
    }

    /**
     * Set list of classes which will not be transformed.
     *
     * @param array $black_list
     */
    public function setBlackList(array $black_list)
    {
        array_push($black_list, 'Delusion', 'TokenReflection');
        $black_list = array_map([$this, 'formatClass'], $black_list);
        $black_list = array_unique($black_list);
        $this->black_list = array_values($black_list);
    }

    /**
     * Add a namespace to black list.
     *
     * @param string $namespace Full or part of namespace
     */
    public function addToBlackList($namespace)
    {
        $namespace = $this->formatClass($namespace);
        array_push($this->black_list, $namespace);
        $this->black_list = array_unique($this->black_list);
    }

    /**
     * Remove from black list all namespaces which starts with specified namespace.
     *
     * @param string $namespace
     */
    public function removeFromBlackList($namespace)
    {
        if ($namespace == 'Delusion' || $namespace == 'TokenReflection') {
            return;
        }
        foreach ($this->black_list as $i => $pattern) {
            if (strpos($pattern, $namespace) === 0) {
                unset($this->black_list[$i]);
            }
        }
    }

    /**
     * Get list of classes which will be transformed.
     *
     * @return array
     */
    public function getWhiteList()
    {
        return $this->white_list;
    }

    /**
     * Set list of classes which will be transformed.
     *
     * @param array $white_list
     */
    public function setWhiteList(array $white_list)
    {
        $white_list = array_map([$this, 'formatClass'], $white_list);
        $position = array_search('Delusion', $white_list);
        if ($position !== false) {
            array_splice($white_list, $position, 1);
        }
        $position = array_search('TokenReflection', $white_list);
        if ($position !== false) {
            array_splice($white_list, $position, 1);
        }
        $this->white_list = array_values(array_unique($white_list));
    }

    /**
     * Add a namespace to white list.
     *
     * @param string $namespace Full or part of namespace
     */
    public function addToWhiteList($namespace)
    {
        if ($namespace == 'Delusion' || $namespace == 'TokenReflection') {
            return;
        }
        $namespace = $this->formatClass($namespace);
        array_push($this->white_list, $namespace);
        $this->white_list = array_unique($this->white_list);
    }

    /**
     * Remove from white list all namespaces which starts with specified namespace.
     *
     * @param string $namespace
     */
    public function removeFromWhiteList($namespace)
    {
        foreach ($this->white_list as $i => $pattern) {
            if (strpos($pattern, $namespace) === 0) {
                unset($this->white_list[$i]);
            }
        }
    }

    /**
     * Set loads class strategy.
     *
     * @param int $strategy
     */
    public function setStrategy($strategy)
    {
        $this->strategy = intval($strategy, 10);
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
        $class = $this->formatClass($class);
        if (empty($this->static_classes[$class])) {
            $this->static_classes[$class] = new ClassBehavior($this->broker->getClass($class));
        }

        return $this->static_classes[$class];
    }

    /**
     * Get behavior condition code.
     *
     * @param string $original_code
     * @param bool $static
     *
     * @return string
     */
    private function getMethodSwitcher($original_code, $static)
    {
        return sprintf(
            'if (%s) { %s } else { %s }',
            $this->getMethodBehaviorCondition($static),
            $this->getMethodReturnCode($static),
            $original_code
        );
    }

    /**
     * Format class.
     *
     * @param string $class
     *
     * @return string
     */
    private function formatClass($class)
    {
        return ltrim($class, '\\');
    }

    /**
     * Check if namespace exists in list.
     *
     * @param array $list
     * @param string $namespace
     *
     * @return bool
     */
    private function inList(array $list, $namespace)
    {
        foreach ($list as $pattern) {
            if (strpos($namespace, $pattern) === 0) {
                return true;
            }
        }

        return false;
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
        $prefix = self::$instance->prefix;
        $injected_code = <<<END
    protected \${$prefix}invokes = [];
    protected \${$prefix}returns = [];

    public function delusionGetInvokesCount(\$method)
    {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }

    public function delusionGetInvokesArguments(\$method)
    {
        return array_key_exists(\$method, \$this->{$prefix}invokes) ? \$this->{$prefix}invokes[\$method] : [];
    }

    public function delusionSetBehavior(\$method, \$returns)
    {
        \$this->{$prefix}returns[\$method] = \$returns;
    }

    public function delusionResetBehavior(\$method)
    {
        unset(\$this->{$prefix}returns[\$method]);
    }

    public function delusionHasCustomBehavior(\$method)
    {
        return array_key_exists(\$method, \$this->{$prefix}returns);
    }

    public function delusionResetAllBehavior()
    {
        \$this->{$prefix}returns = [];
    }

    public function delusionResetInvokesCounter(\$method)
    {
        unset(\$this->{$prefix}invokes[\$method]);
    }

    public function delusionResetAllInvokesCounter()
    {
        \$this->{$prefix}invokes = [];
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
        $prefix = self::$instance->prefix;
        $definition = sprintf('$%s = \Delusion\Delusion::injection();', $prefix) . PHP_EOL;

        return $definition . sprintf('$%1$sclass = $%1$s->getClassBehavior(__CLASS__);', $prefix) . PHP_EOL;
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
        $prefix = self::$instance->prefix;
        if ($static) {
            return sprintf('$%sclass->delusionHasCustomBehavior(__FUNCTION__)', $prefix);
        } else {
            return sprintf('array_key_exists(__FUNCTION__, $this->%sreturns)', $prefix);
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
        $prefix = self::$instance->prefix;
        if ($static) {
            return sprintf('$%sclass->registerInvoke(__FUNCTION__, func_get_args());', $prefix);
        } else {
            return <<<END
        if (empty(\$this->{$prefix}invokes[__FUNCTION__])) {
            \$this->{$prefix}invokes[__FUNCTION__] = [];
        }
        array_push(\$this->{$prefix}invokes[__FUNCTION__], func_get_args());
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
        $prefix = self::$instance->prefix;
        if ($static) {
            $return_code = sprintf('$%1$sreturn = $%1$sclass->getCustomBehavior(__FUNCTION__);', $prefix);
        } else {
            $return_code = sprintf('$%1$sreturn = $this->%1$sreturns[__FUNCTION__];', $prefix);
        }
        $return_code .= <<<END

            if (is_callable(\${$prefix}return)) {
                return \${$prefix}return(func_get_args());
            } else {
                return \${$prefix}return;
            }
END;

        return $return_code;
    }

    /**
     * Load class by its name.
     *
     * @param string $class
     *
     * @return bool
     */
    private function loadClass($class)
    {
        $class = $this->formatClass($class);
        if ($this->strategy === self::STRATEGY_ALLOW) {
            $use_custom_loader = !$this->inList($this->black_list, $class);
        } else {
            $use_custom_loader = $this->inList($this->white_list, $class);
        }
        if ($use_custom_loader) {
            if (!$this->broker->hasClass($class)) {
                $file = $this->composer->findFile($class);
                if (empty($file)) {
                    return false;
                }
                $this->broker->processFile($file);
                $this->current_class = $class;
                include('php://filter/read=delusion.loader/resource=' . $file);

                return true;
            }
        }

        return $this->composer->loadClass($class) ? true : false;
    }
}
