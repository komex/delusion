<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Interface ConfiguratorInterface
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ConfiguratorInterface
{
    /**
     * Returns number of method invokes.
     *
     * @param string $method
     *
     * @return int
     */
    public function getInvokesCount($method);

    /**
     * Return the array of array of arguments with which the method was invoked.
     *
     * @param string $method
     *
     * @return array[]
     */
    public function getInvokes($method);

    /**
     * Clear invokes stack for method.
     *
     * @param string $method
     */
    public function resetInvokes($method);

    /**
     * Clear all invokes stack.
     */
    public function resetAllInvokes();

    /**
     * Register new method invoke.
     *
     * @param string $method
     * @param array $arguments
     */
    public function registerInvoke($method, array $arguments);

    /**
     * Get result of custom behavior for specified method.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function getCustomBehavior($method, array $arguments);

    /**
     * Check if method has custom behavior and register invoke.
     *
     * @param string $method Method name
     *
     * @return bool
     */
    public function hasCustomBehavior($method);

    /**
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function setCustomBehavior($method, $returns);

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function resetCustomBehavior($method);

    /**
     * Reset class to original state.
     */
    public function resetAllCustomBehavior();
}
