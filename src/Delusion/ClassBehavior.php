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
class ClassBehavior implements DelusionInterface
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
        $this->invokes = [];
    }

    /**
     * Register new method invoke.
     *
     * @param string $method
     * @param array $arguments
     */
    public function delusionRegisterInvoke($method, array $arguments)
    {
        if (empty($this->invokes[$method])) {
            $this->invokes[$method] = [];
        }
        array_push($this->invokes[$method], $arguments);
    }

    /**
     * Get result of custom behavior for specified method.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function delusionGetCustomBehavior($method, array $arguments)
    {
        $return = $this->returns[$method];
        if (is_callable($return)) {
            $return = call_user_func_array($return, $arguments);
        }

        return $return;
    }

    /**
     * Check if method has custom behavior and register invoke.
     *
     * @param string $method Method name
     *
     * @return bool
     */
    public function delusionHasCustomBehavior($method)
    {
        return array_key_exists($method, $this->returns);
    }

    /**
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function delusionSetCustomBehavior($method, $returns)
    {
        $this->returns[$method] = $returns;
    }

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function delusionResetCustomBehavior($method)
    {
        unset($this->returns[$method]);
    }

    /**
     * Reset class to original state.
     */
    public function delusionResetAllCustomBehavior()
    {
        $this->returns = [];
    }
}
