<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class Suggestible
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
trait Suggestible
{
    /**
     * @var array
     */
    private $__delusion__invokes = [];
    /**
     * @var array
     */
    private $__delusion__returns = [];

    /**
     * @param string $method
     *
     * @return bool
     */
    private static function delusionHasCustomBehaviorStatic($method)
    {
        return Delusion::injection()->getClassBehavior(__CLASS__)->delusionHasCustomBehavior($method);
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    private static function delusionGetCustomBehaviorStatic($method, array $args)
    {
        return Delusion::injection()->getClassBehavior(__CLASS__)->delusionGetCustomBehavior($method, $args);
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private static function delusionRegisterInvokeStatic($method, array $arguments)
    {
        Delusion::injection()->getClassBehavior(__CLASS__)->delusionRegisterInvoke(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private function delusionRegisterInvoke($method, array $arguments)
    {
        if (empty($this->__delusion__invokes[$method])) {
            $this->__delusion__invokes[$method] = [];
        }
        array_push($this->__delusion__invokes[$method], $arguments);
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    private function delusionGetCustomBehavior($method, array $args)
    {
        $return = $this->__delusion__returns[$method];
        if (is_callable($return)) {
            $return = call_user_func_array($return, $args);
        }

        return $return;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    private function delusionHasCustomBehavior($method)
    {
        return array_key_exists($method, $this->__delusion__returns);
    }
}
