<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

use TokenReflection\ReflectionClass;
use TokenReflection\ReflectionMethod;

/**
 * Class Filter
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Filter extends \php_user_filter
{
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
            if (Delusion::injection()->hasCurrentClass()) {
                $bucket->data = $this->spoof();
                $consumed += strlen($bucket->data);
                stream_bucket_append($out, $bucket);
            }
        }

        return PSFS_PASS_ON;
    }

    /**
     * Transform original class for getting full control.
     *
     * @return string
     */
    private function spoof()
    {
        $class = Delusion::injection()->getCurrentClass();
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
     * @throws \LogicException on parsing error
     * @return string
     */
    private function addDelusionInterface($code, ReflectionClass $class)
    {
        if ($class->implementsInterface('Delusion\\PuppetThreadInterface')) {
            return $code;
        }
        $stream = $class->getBroker()->getFileTokens($class->getFileName());
        $position = $class->getStartPosition();
        $interfaces = [];
        $interface_class = '';
        $implement_position = 0;
        while (true) {
            $token = $stream->getTokenName($position);
            switch ($token) {
                case 'T_IMPLEMENTS':
                    $implement_position = $position;
                    break;
                case 'T_NS_SEPARATOR':
                case 'T_STRING':
                    if ($implement_position > 0) {
                        $interface_class .= $stream->getTokenValue($position);
                    }
                    break;
                case ',':
                case 'T_WHITESPACE':
                    if ($implement_position > 0 && !empty($interface_class)) {
                        array_push($interfaces, $interface_class);
                        $interface_class = '';
                    }
                    break;
                case '{':
                    if ($implement_position > 0) {
                        $definition = $stream->getSourcePart($class->getStartPosition(), $implement_position - 1);
                    } else {
                        $definition = $stream->getSourcePart($class->getStartPosition(), $position - 1);
                    }
                    array_push($interfaces, '\\Delusion\\PuppetThreadInterface');
                    $definition = trim($definition) . ' implements ' . join(', ', $interfaces) . PHP_EOL . '{';

                    return str_replace(
                        $stream->getSourcePart($class->getStartPosition(), $position),
                        $definition,
                        $code
                    );
            }
            $position++;
        }
        throw new \LogicException(
            'Failed to add \\Delusion\\PuppetThreadInterface to implements for class ' . $class->getName()
        );
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
        $prefix = Delusion::injection()->getPrefix();
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
        $prefix = Delusion::injection()->getPrefix();
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
        $prefix = Delusion::injection()->getPrefix();
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
        $prefix = Delusion::injection()->getPrefix();
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
        $prefix = Delusion::injection()->getPrefix();
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
}
