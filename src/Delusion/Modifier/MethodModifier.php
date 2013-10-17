<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Delusion\Modifier;

/**
 * Class MethodParser
 *
 * @package Delusion\Modifier
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class MethodModifier extends Modifier
{
    /**
     * @var int
     */
    protected $balance = 0;
    /**
     * @var bool
     */
    protected $inHereDoc = false;

    /**
     * @param int|string $type
     * @param string $value
     *
     * @return string
     */
    public function process($type, $value)
    {
        if ($this->inHereDoc) {
            if ($type === T_END_HEREDOC) {
                $this->inHereDoc = false;
            }
        } elseif ($type === '{') {
            $this->balance++;
        } elseif ($type === '}') {
            $this->balance--;
            if ($this->balance < 0) {
                $this->injector->setModifier(new MethodParser());
            }
        } elseif ($type === T_START_HEREDOC) {
            $this->inHereDoc = true;
        }

        return $value;
    }
}
