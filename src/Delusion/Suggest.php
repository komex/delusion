<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Class Suggest
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
trait Suggest
{
    /**
     * @var bool
     */
    private static $__delusion__registerInvokes = false;
    /**
     * @var array
     */
    private $__delusion__invokes = [];
    /**
     * @var array
     */
    private $__delusion__returns = [];

    /**
     * @return array
     */
    public function &delusionGetInvokes()
    {
        return $this->__delusion__invokes;
    }

    /**
     * @return array
     */
    public function &delusionGetReturns()
    {
        return $this->__delusion__returns;
    }

    /**
     * @return bool
     */
    public function delusionDoesRegisterInvokes()
    {
        return self::$__delusion__registerInvokes;
    }

    /**
     * @param bool $register
     */
    public function delusionRegisterInvokes($register)
    {
        self::$__delusion__registerInvokes = !!$register;
    }
}
