<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion;

/**
 * Interface Suggestible
 *
 * @package Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface Suggestible
{
    /**
     * @return array
     */
    public function delusionGetInvokes();

    /**
     * @return array
     */
    public function delusionGetReturns();

    /**
     * @return bool
     */
    public function delusionDoesRegisterInvokes();

    /**
     * @param bool $register
     */
    public function delusionRegisterInvokes($register);
}
