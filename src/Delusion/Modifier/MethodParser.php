<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion\Modifier;

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
            $this->injector->setModifier(new Modifier());
            $value = 'use \Delusion\ClassController;' . $value;
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
        $template = 'self::delusionRegisterInvoke(%s, __FUNCTION__, func_get_args()); ';
        if ($this->static || $this->isConstructor) {
            $condition .= sprintf($template, '\Delusion\Delusion::injection()->getSuggest(__CLASS__)');
            $condition .= 'if (self::delusionCustomBehaviorStatic(__FUNCTION__, func_get_args())) ';
        } else {
            $condition .= sprintf($template, '$this');
            $condition .= 'if ($this->delusionCustomBehavior(__FUNCTION__, func_get_args())) ';
        }
        $condition .= 'return self::delusionGetResults();';

        return $condition;
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
