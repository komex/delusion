<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class Configurator
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Configurator
{
    /**
     * Returns number of method invokes.
     *
     * @param Suggestible $class
     * @param string $method
     *
     * @return int
     */
    public static function getInvokesCount(Suggestible $class, $method)
    {
        return count(self::getInvokes($class, $method));
    }

    /**
     * Return the array of array of arguments with which the method was invoked.
     *
     * @param Suggestible $class
     * @param string $method
     *
     * @return array[]
     */
    public static function getInvokes(Suggestible $class, $method)
    {
        $invokes = $class->delusionGetInvokes();

        return array_key_exists($method, $invokes) ? $invokes[$method] : [];
    }

    /**
     * Clear invokes stack for method.
     *
     * @param Suggestible $class
     * @param string $method
     */
    public static function resetInvokes(Suggestible $class, $method)
    {
        $invokes = & $class->delusionGetInvokes();
        unset($invokes[$method]);
    }

    /**
     * Clear all invokes stack.
     *
     * @param Suggestible $class
     */
    public static function resetAllInvokes(Suggestible $class)
    {
        $invokes = & $class->delusionGetInvokes();
        $invokes = [];
    }

    /**
     * Register new method invoke.
     *
     * @param Suggestible $class
     * @param string $method
     * @param array $arguments
     */
    public static function registerInvoke(Suggestible $class, $method, array $arguments)
    {
        $invokes = & $class->delusionGetInvokes();
        if (empty($invokes[$method])) {
            $invokes[$method] = [];
        }
        array_push($invokes[$method], $arguments);
    }

    /**
     * Get result of custom behavior for specified method.
     *
     * @param Suggestible $class
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public static function getCustomBehavior(Suggestible $class, $method, array $arguments)
    {
        $returns = & $class->delusionGetReturns();
        $return = $returns[$method];
        if (is_callable($return)) {
            $return = call_user_func_array($return, $arguments);
        }

        return $return;
    }

    /**
     * Check if method has custom behavior and register invoke.
     *
     * @param Suggestible $class
     * @param string $method Method name
     *
     * @return bool
     */
    public static function hasCustomBehavior(Suggestible $class, $method)
    {
        $return = & $class->delusionGetReturns();

        return array_key_exists($method, $return);
    }

    /**
     * Set behavior for method.
     *
     * @param Suggestible $class
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public static function setCustomBehavior(Suggestible $class, $method, $returns)
    {
        $return = & $class->delusionGetReturns();
        $return[$method] = $returns;
    }

    /**
     * Reset behavior for method to default.
     *
     * @param Suggestible $class
     * @param string $method
     */
    public static function resetCustomBehavior(Suggestible $class, $method)
    {
        $return = & $class->delusionGetReturns();
        unset($return[$method]);
    }

    /**
     * Reset class to original state.
     *
     * @param Suggestible $class
     */
    public static function resetAllCustomBehavior(Suggestible $class)
    {
        $return = & $class->delusionGetReturns();
        $return = [];
    }
}
