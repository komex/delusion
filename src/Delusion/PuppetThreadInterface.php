<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Interface PuppetThreadInterface
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface PuppetThreadInterface
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
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function delusionSetBehavior($method, $returns);

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function delusionResetBehavior($method);
}
