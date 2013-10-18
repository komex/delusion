<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class Suggest
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
trait Suggest
{
    /**
     * @var bool
     */
    private static $__delusion__registerInvokes = false;
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
        return Delusion::injection()->getClassBehavior(__CLASS__)->hasCustomBehavior($method);
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    private static function delusionGetCustomBehaviorStatic($method, array $args)
    {
        return Delusion::injection()->getClassBehavior(__CLASS__)->getCustomBehavior($method, $args);
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private static function delusionRegisterInvokeStatic($method, array $arguments)
    {
        if (self::$__delusion__registerInvokes) {
            Delusion::injection()->getClassBehavior(__CLASS__)->registerInvoke(__FUNCTION__, func_get_args());
        }
    }

    /**
     * @return array
     */
    public function &delusionGetInvokes()
    {
        return $this->__delusion__invokes;
    }

    /**
     * @return array
     */
    public function &delusionGetReturns()
    {
        return $this->__delusion__returns;
    }

    /**
     * @return bool
     */
    public function delusionDoesRegisterInvokes()
    {
        return self::$__delusion__registerInvokes;
    }

    /**
     * @param bool $register
     */
    public function delusionRegisterInvokes($register)
    {
        self::$__delusion__registerInvokes = !!$register;
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private function delusionRegisterInvoke($method, array $arguments)
    {
        if (self::$__delusion__registerInvokes) {
            if (empty($this->__delusion__invokes[$method])) {
                $this->__delusion__invokes[$method] = [];
            }
            array_push($this->__delusion__invokes[$method], $arguments);
        }
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
