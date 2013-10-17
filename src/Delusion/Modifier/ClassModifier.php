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
    /**
     * @var bool
     */
    protected $implemented = false;

    public function process($type, $value)
    {
        switch ($type) {
            case T_WHITESPACE:
                $this->whiteSpace = $value;

                return '';
            case T_IMPLEMENTS:
                $whiteSpace = $this->whiteSpace;
                $this->whiteSpace = '';
                $this->implemented = true;

                return $whiteSpace . 'implements \\Delusion\\DelusionInterface,';
            case '{':
                $this->injector->setModifier(new MethodParser());
                $implements = $this->implemented ? '' : ' implements \\Delusion\\DelusionInterface';

                return $implements . $this->whiteSpace . $value;
            default:
                $whiteSpace = $this->whiteSpace;
                $this->whiteSpace = '';

                return $whiteSpace . $value;
        }
    }
}
