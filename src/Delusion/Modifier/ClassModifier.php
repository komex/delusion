<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Delusion\Modifier;

/**
 * Class ClassParser
 *
 * @package Delusion\Modifier
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ClassModifier extends Modifier
{
    /**
     * @var string
     */
    protected $whiteSpace = '';

    public function in($type, $value)
    {
        switch ($type) {
            case T_WHITESPACE:
                $this->whiteSpace = $value;

                return '';
            case T_IMPLEMENTS:
                $this->filter->setModifier(new MethodModifier());

                return $this->whiteSpace . 'implements \\Delusion\\PuppetThreadInterface,';
            case '{':
                $this->filter->setModifier(new MethodModifier());

                return ' implements \\Delusion\\PuppetThreadInterface' . $this->whiteSpace . '{';
            default:
                $whiteSpace = $this->whiteSpace;
                $this->whiteSpace = '';

                return $whiteSpace . $value;
        }
    }
}
