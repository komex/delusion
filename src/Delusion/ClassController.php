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
     * @param string $method
     *
     * @return bool
     */
    private static function delusionHasCustomBehaviorStatic($method)
    {
        return Configurator::hasCustomBehavior(Delusion::injection()->getSuggest(__CLASS__), $method);
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    private static function delusionGetCustomBehaviorStatic($method, array $arguments)
    {
        return Configurator::getCustomBehavior(Delusion::injection()->getSuggest(__CLASS__), $method, $arguments);
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
     */
    private function delusionRegisterInvoke($method, array $arguments)
    {
        if (self::$__delusion__registerInvokes) {
            Configurator::registerInvoke($this, $method, $arguments);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    private function delusionGetCustomBehavior($method, array $arguments)
    {
        return Configurator::getCustomBehavior($this, $method, $arguments);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    private function delusionHasCustomBehavior($method)
    {
        return Configurator::hasCustomBehavior($this, $method);
    }
}
