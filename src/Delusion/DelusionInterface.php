<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Interface DelusionInterface
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface DelusionInterface
{
    /**
     * Returns number of method invokes.
     *
     * @param string $method
     *
     * @return int
     */
    public function delusionGetInvokesCount($method);

    /**
     * Return the array of array of arguments with which the method was invoked.
     *
     * @param string $method
     *
     * @return array[]
     */
    public function delusionGetInvokesArguments($method);

    /**
     * Clear invokes stack for method.
     *
     * @param string $method
     */
    public function delusionResetInvokesCounter($method);

    /**
     * Clear all invokes stack.
     */
    public function delusionResetAllInvokesCounter();

    /**
     * Register new method invoke.
     *
     * @param string $method
     * @param array $arguments
     */
    public function delusionRegisterInvoke($method, array $arguments);

    /**
     * Get result of custom behavior for specified method.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function delusionGetCustomBehavior($method, array $arguments);

    /**
     * Check if method has custom behavior and register invoke.
     *
     * @param string $method Method name
     *
     * @return bool
     */
    public function delusionHasCustomBehavior($method);

    /**
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function delusionSetCustomBehavior($method, $returns);

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function delusionResetCustomBehavior($method);

    /**
     * Reset class to original state.
     */
    public function delusionResetAllCustomBehavior();
}
