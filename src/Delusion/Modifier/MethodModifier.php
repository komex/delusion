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

    public function in($type, $value)
    {
        if (!$this->inHereDoc) {
            if ($type === '{') {
                if ($this->balance === 0) {
                    $value .= $this->getMethodCode();
                }
                $this->balance++;
            } elseif ($type === '}') {
                $this->balance--;
                if ($this->balance < 0) {
                    $value = $this->getDelusionMethods() . $value;
                }
            } elseif ($type === T_START_HEREDOC) {
                $this->inHereDoc = true;
            }
        } elseif ($type === T_END_HEREDOC) {
            $this->inHereDoc = false;
        }

        return $value;
    }

    /**
     * @return string
     */
    protected function getMethodCode()
    {
        return 'SUPER CODE!!';
    }

    /**
     * @return string
     */
    public function getDelusionMethods()
    {
        return 'DELUSION METHODS!';
    }
}
