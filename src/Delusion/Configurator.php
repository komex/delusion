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
     * @param string|Suggestible $class
     * @param string $method
     *
     * @return bool
     */
    public static function hasBehavior($class, $method)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $returns = & $class->delusionGetReturns();

            return isset($returns[$method]);
        }

        return false;
    }

    /**
     * @param string|Suggestible $class
     * @param string $method
     * @param mixed $behavior
     */
    public static function setBehavior($class, $method, $behavior)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $returns = & $class->delusionGetReturns();
            $returns[$method] = $behavior;
        }
    }

    /**
     * @param string|Suggestible $class
     * @param string $method
     */
    public static function resetBehavior($class, $method)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $returns = & $class->delusionGetReturns();
            unset($returns[$method]);
        }
    }

    /**
     * @param string|Suggestible $class
     */
    public static function resetAllBehaviors($class)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $returns = & $class->delusionGetReturns();
            $returns = [];
        }
    }

    /**
     * @param string|Suggestible $class
     * @param null|bool $register
     *
     * @return bool Actual state of this flag
     */
    public static function registerInvokes($class, $register = null)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            if ($register !== null) {
                $class->delusionRegisterInvokes($register);
            }

            return $class->delusionDoesRegisterInvokes();
        }

        return false;
    }

    /**
     * @param string|Suggestible $class
     * @param string $method
     *
     * @return array
     */
    public static function getInvokes($class, $method)
    {
        $invokes = self::getAllInvokes($class);

        return (isset($invokes[$method])) ? $invokes[$method] : [];
    }

    /**
     * @param string|Suggestible $class
     *
     * @return array
     */
    public static function getAllInvokes($class)
    {
        $invokes = [];
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $invokes = $class->delusionGetInvokes();
        }

        return $invokes;
    }

    /**
     * @param string|Suggestible $class
     * @param string $method
     */
    public static function resetInvokes($class, $method)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $invokes = & $class->delusionGetInvokes();
            unset($invokes[$method]);
        }
    }

    /**
     * @param string|Suggestible $class
     */
    public static function resetAllInvokes($class)
    {
        if (is_string($class)) {
            // @todo global storage
        } elseif ($class instanceof Suggestible) {
            $invokes = & $class->delusionGetInvokes();
            $invokes = [];
        }
    }
}
