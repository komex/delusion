<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Delusion\Modifier;

use Delusion\Injector;

/**
 * Class Modifier
 *
 * @package Delusion\Modifier
 */
class Modifier
{
    /**
     * @var Injector
     */
    protected $injector;

    /**
     * @param Injector $injector
     */
    public function setInjector(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * Get new token.
     *
     * @param int|string $type
     * @param string $value
     *
     * @return string
     */
    public function process($type, $value)
    {
        if ($type === T_CLASS) {
            $this->injector->setModifier(new ClassModifier());
        } elseif ($type === T_TRAIT) {
            // @todo: Trait parser
        }

        return $value;
    }
}
