<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion\Resources;

/**
 * Class SimpleClassA
 *
 * @package Tests\Delusion\Resources
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SimpleClassA
{
    public $log = [];

    public function __construct()
    {
        array_push($this->log, __FUNCTION__);
    }

    public function publicMethod($some_argument = 2)
    {
        array_push($this->log, __FUNCTION__);

        return $some_argument + 1;
    }

    protected function protectedMethod($some_argument)
    {
        array_push($this->log, __FUNCTION__);

        return $some_argument + 2;
    }

    private function privateMethod($some_argument)
    {
        array_push($this->log, __FUNCTION__);

        return $some_argument + 3;
    }

    public function callProtected($some_argument)
    {
        array_push($this->log, __FUNCTION__);

        return $this->protectedMethod($some_argument) + 4;
    }

    public function callPrivate($some_argument)
    {
        array_push($this->log, __FUNCTION__);

        return $this->privateMethod($some_argument) + 5;
    }
}
