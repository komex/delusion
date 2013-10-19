<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class ClassController
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
trait ClassController
{
    use Suggest;

    /**
     * @var mixed
     */
    private static $delusionResult;

    /**
     * @param $method
     * @param array $arguments
     *
     * @return bool
     */
    private static function delusionCustomBehaviorStatic($method, array $arguments)
    {
        $class = Delusion::injection()->getSuggest(__CLASS__);
        if (Configurator::hasCustomBehavior($class, $method)) {
            self::$delusionResult = Configurator::getCustomBehavior($class, $method, $arguments);

            return true;
        }

        return false;
    }

    /**
     * Get result of custom behavior.
     *
     * @return mixed
     */
    private static function delusionGetResults()
    {
        $result = self::$delusionResult;
        self::$delusionResult = null;

        return $result;
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private static function delusionRegisterInvokeStatic($method, array $arguments)
    {
        if (self::$__delusion__registerInvokes) {
            Configurator::registerInvoke(Delusion::injection()->getSuggest(__CLASS__), $method, $arguments);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return bool
     */
    private function delusionCustomBehavior($method, array $arguments)
    {
        /** @var Suggestible $this */
        if (Configurator::hasCustomBehavior($this, $method)) {
            self::$delusionResult = Configurator::getCustomBehavior($this, $method, $arguments);

            return true;
        } elseif (self::delusionCustomBehaviorStatic($method, $arguments)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private function delusionRegisterInvoke($method, array $arguments)
    {
        /** @var Suggestible $this */
        if (self::$__delusion__registerInvokes) {
            Configurator::registerInvoke($this, $method, $arguments);
        }
    }
}
