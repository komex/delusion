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
class Configurator implements ConfiguratorInterface
{
    /**
     * @var ConfiguratorInterface
     */
    protected $configurator;
    /**
     * @var Suggestible
     */
    protected $suggest;

    /**
     * @param string|Suggestible $class
     */
    public function __construct($class)
    {
        if (is_string($class)) {
            $this->configurator = Delusion::injection()->getClassBehavior($class);
        } elseif ($class instanceof Suggestible) {
            $this->suggest = $class;
        }
    }

    /**
     * Returns number of method invokes.
     *
     * @param string $method
     *
     * @return int
     */
    public function getInvokesCount($method)
    {
        return count($this->getInvokes($method));
    }

    /**
     * Return the array of array of arguments with which the method was invoked.
     *
     * @param string $method
     *
     * @return array[]
     */
    public function getInvokes($method)
    {
        if ($this->configurator !== null) {
            return $this->configurator->getInvokes($method);
        } else {
            $invokes = $this->suggest->delusionGetInvokes();

            return array_key_exists($method, $invokes) ? $invokes[$method] : [];
        }
    }

    /**
     * Clear invokes stack for method.
     *
     * @param string $method
     */
    public function resetInvokes($method)
    {
        if ($this->configurator !== null) {
            $this->configurator->resetInvokes($method);
        } else {
            $invokes = & $this->suggest->delusionGetInvokes();
            unset($invokes[$method]);
        }
    }

    /**
     * Clear all invokes stack.
     */
    public function resetAllInvokes()
    {
        if ($this->configurator !== null) {
            $this->configurator->resetAllInvokes();
        } else {
            $invokes = & $this->suggest->delusionGetInvokes();
            $invokes = [];
        }
    }

    /**
     * Register new method invoke.
     *
     * @param string $method
     * @param array $arguments
     */
    public function registerInvoke($method, array $arguments)
    {
        if ($this->configurator !== null) {
            $this->configurator->registerInvoke($method, $arguments);
        } else {
            $invokes = & $this->suggest->delusionGetInvokes();
            if (empty($invokes[$method])) {
                $invokes[$method] = [];
            }
            array_push($invokes[$method], $arguments);
        }
    }

    /**
     * Get result of custom behavior for specified method.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function getCustomBehavior($method, array $arguments)
    {
        if ($this->configurator !== null) {
            return $this->configurator->getCustomBehavior($method, $arguments);
        } else {
            $return = & $this->suggest->delusionGetReturns();
            if (is_callable($return)) {
                $return = call_user_func_array($return, $arguments);
            }

            return $return;
        }
    }

    /**
     * Check if method has custom behavior and register invoke.
     *
     * @param string $method Method name
     *
     * @return bool
     */
    public function hasCustomBehavior($method)
    {
        if ($this->configurator !== null) {
            return $this->configurator->hasCustomBehavior($method);
        } else {
            $return = & $this->suggest->delusionGetReturns();

            return array_key_exists($method, $return);
        }
    }

    /**
     * Set behavior for method.
     *
     * @param string $method
     * @param mixed $returns What shall method returns
     */
    public function setCustomBehavior($method, $returns)
    {
        if ($this->configurator !== null) {
            $this->configurator->setCustomBehavior($method, $returns);
        } else {
            $return = & $this->suggest->delusionGetReturns();
            $return[$method] = $returns;
        }
    }

    /**
     * Reset behavior for method to default.
     *
     * @param string $method
     */
    public function resetCustomBehavior($method)
    {
        if ($this->configurator !== null) {
            $this->configurator->resetCustomBehavior($method);
        } else {
            $return = & $this->suggest->delusionGetReturns();
            unset($return[$method]);
        }
    }

    /**
     * Reset class to original state.
     */
    public function resetAllCustomBehavior()
    {
        if ($this->configurator !== null) {
            $this->configurator->resetAllCustomBehavior();
        } else {
            $return = & $this->suggest->delusionGetReturns();
            $return = [];
        }
    }
}
