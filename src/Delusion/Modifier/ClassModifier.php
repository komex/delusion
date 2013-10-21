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
                $value = '';
                break;
            case T_IMPLEMENTS:
                $this->implemented = true;
                $whiteSpace = $this->whiteSpace;
                $this->whiteSpace = '';
                $value = $whiteSpace . $value;
                break;
            case '{':
                $this->injector->setModifier(new MethodParser());
                $implements = $this->implemented ? ',' : ' implements';
                $value = $implements . ' \\Delusion\\Suggestible' . $this->whiteSpace . $value;
                break;
            default:
                $whiteSpace = $this->whiteSpace;
                $this->whiteSpace = '';
                $value = $whiteSpace . $value;
        }
        return $value;
    }
}
