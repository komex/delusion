<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion\Modifier;

use Delusion\Delusion;

/**
 * Class MethodParser
 *
 * @package Delusion\Modifier
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class MethodParser extends Modifier
{
    /**
     * @var bool
     */
    protected $static = false;
    /**
     * @var bool
     */
    protected $isConstructor = false;
    /**
     * @var bool
     */
    protected $waitStaticTarget = false;
    /**
     * @var bool
     */
    protected $waitFunctionName = false;

    /**
     * @param int|string $type
     * @param string $value
     *
     * @return string
     */
    public function process($type, $value)
    {
        if ($type === T_STATIC) {
            $this->waitStaticTarget = true;
        } elseif ($type === T_FUNCTION) {
            $this->waitFunctionName();
        } elseif ($type === T_VARIABLE && $this->waitStaticTarget) {
            $this->waitStaticTarget = false;
            $this->static = false;
        } elseif ($type === T_STRING && $this->waitFunctionName) {
            $this->receivedFunctionName($value);
        } elseif ($type === '{') {
            $this->injector->setModifier(new MethodModifier());
            $value .= $this->getMethodCode();
        } elseif ($type === '}') {
            $value = $this->getDelusionMethods() . $value;
        }

        return $value;
    }

    /**
     * Get method controller code.
     *
     * @return string
     */
    protected function getMethodCode()
    {
        $condition = '';
        $invoke = '$this';
        $prefix = Delusion::injection()->getPrefix();
        $class = '$' . $prefix . 'class';
        if ($this->isConstructor) {
            $condition .= $class . ' = \\Delusion\\Delusion::injection()->getClassBehavior(__CLASS__); ';
        } elseif ($this->static) {
            $invoke = '$' . $prefix . 'class';
            $condition .= $class . ' = \\Delusion\\Delusion::injection()->getClassBehavior(__CLASS__); ';
        }

        $condition .= sprintf('%s->delusionRegisterInvoke(__FUNCTION__, func_get_args()); ', $invoke);
        if ($this->static) {
            $condition .= sprintf('if (%s->delusionHasCustomBehavior(__FUNCTION__)) ', $class);
            $condition .= sprintf('return %s->delusionGetCustomBehavior(__FUNCTION__, func_get_args());', $class);
        } else {
            $condition .= sprintf('if ($this->%shasBehavior(__FUNCTION__)) ', $prefix);
            $condition .= sprintf('return $this->%sgetBehavior(__FUNCTION__, func_get_args());', $prefix);
        }

        return $condition;
    }

    /**
     * Get code for control class.
     *
     * @return string
     */
    protected function getDelusionMethods()
    {
        $prefix = Delusion::injection()->getPrefix();

        return <<<END
    protected \${$prefix}invokes = [];
    protected \${$prefix}returns = [];
    protected function {$prefix}hasBehavior(\$method) {
        return (\$this->delusionHasCustomBehavior(\$method) ||
            \Delusion\Delusion::injection()->getClassBehavior(__CLASS__)->delusionHasCustomBehavior(\$method));
    }
    protected function {$prefix}getBehavior(\$method, array \$arguments) {
        if (\$this->delusionHasCustomBehavior(\$method)) {
            return \$this->delusionGetCustomBehavior(\$method, \$arguments);
        } else {
            \$class = \Delusion\Delusion::injection()->getClassBehavior(__CLASS__);
            return \$class->delusionGetCustomBehavior(\$method, \$arguments);
        }
    }
    public function delusionGetInvokesCount(\$method) {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }
    public function delusionGetInvokesArguments(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}invokes) ? \$this->{$prefix}invokes[\$method] : [];
    }
    public function delusionResetInvokesCounter(\$method) {
        unset(\$this->{$prefix}invokes[\$method]);
    }
    public function delusionResetAllInvokesCounter() {
        \$this->{$prefix}invokes = [];
    }
    public function delusionRegisterInvoke(\$method, array \$arguments) {
        if (empty(\$this->{$prefix}invokes[\$method])) {
            \$this->{$prefix}invokes[\$method] = [];
        }
        array_push(\$this->{$prefix}invokes[\$method], \$arguments);
    }
    public function delusionGetCustomBehavior(\$method, array \$args) {
        \$return = \$this->{$prefix}returns[\$method];
        if (is_callable(\$return)) {
            \$return = call_user_func_array(\$return, \$args);
        }
        return \$return;
    }
    public function delusionHasCustomBehavior(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}returns);
    }
    public function delusionSetCustomBehavior(\$method, \$returns) {
        \$this->{$prefix}returns[\$method] = \$returns;
    }
    public function delusionResetCustomBehavior(\$method) {
        unset(\$this->{$prefix}returns[\$method]);
    }
    public function delusionResetAllCustomBehavior() {
        \$this->{$prefix}returns = [];
    }

END;
    }

    /**
     * Received method name.
     *
     * @param string $name Method name
     */
    private function receivedFunctionName($name)
    {
        $this->waitFunctionName = false;
        $this->isConstructor = false;
        if ($name === '__construct') {
            $this->waitStaticTarget = false;
            $this->static = true;
            $this->isConstructor = true;
        }
    }

    /**
     * Set status waiting function name.
     */
    private function waitFunctionName()
    {
        $this->waitFunctionName = true;
        if ($this->waitStaticTarget) {
            $this->waitStaticTarget = false;
            $this->static = true;
        }
    }
}
