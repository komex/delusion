<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class ClassBehavior
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ClassBehavior implements PuppetThreadInterface
{
    /**
     * @var array[]
     */
    protected $invokes = [];
    /**
     * @var array[]
     */
    protected $returns = [];

    /**
     * Reset class to original state.
     */
    public function delusionResetAllBehavior()
    {
        $this->returns = [];
    }

    /**
     * Clear invokes stack for method.
     *
     * @param string $method
     */
    public function delusionResetInvokesCounter($method)
    {
        unset($this->invokes[$method]);
    }

    /**
     * Clear all invokes stack.
     */
    public function delusionResetAllInvokesCounter()
    {
        unset($this->invokes);
    }

    /**
     * Returns number of method invokes.
     *
     * @param string $method
     *
     * @return int
     */
    public function delusionGetInvokesCount($method)
    {
        return count($this->delusionGetInvokesArguments($method));
    }

    /**
     * Return the array of array of arguments with which the method was invoked.
     *
     * @param string $method
     *
     * @return array[]
     */
    public function delusionGetInvokesArguments($method)
    {
        return array_key_exists($method, $this->invokes) ? $this->invokes[$method] : [];
    }

    /**
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function delusionSetBehavior($method, $returns)
    {
        $this->returns[$method] = $returns;
    }

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function delusionResetBehavior($method)
    {
        unset($this->returns[$method]);
    }

    /**
     * Check if method has custom behavior.
     *
     * @param string $method
     *
     * @return bool
     */
    public function delusionHasCustomBehavior($method)
    {
        return array_key_exists($method, $this->returns);
    }

    /**
     * Register static method invoke.
     *
     * @param string $method Method name
     * @param array $arguments
     */
    public function registerInvoke($method, array $arguments)
    {
        if (empty($this->invokes[$method])) {
            $this->invokes[$method] = [];
        }
        array_push($this->invokes[$method], $arguments);
    }

    /**
     * Get custom behavior for method.
     *
     * @param string $method
     *
     * @return mixed
     */
    public function getCustomBehavior($method)
    {
        return $this->delusionHasCustomBehavior($method) ? $this->returns[$method] : null;
    }
}
