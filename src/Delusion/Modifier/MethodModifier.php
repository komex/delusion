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
     * @var bool
     */
    protected $static = false;

    /**
     * @param int|string $type
     * @param string $value
     *
     * @return string
     */
    public function in($type, $value)
    {
        if ($this->inHereDoc) {
            if ($type === T_END_HEREDOC) {
                $this->inHereDoc = false;
            }

            return $value;
        }
        if ($type === '{') {
            if ($this->balance === 0) {
                $value .= $this->getMethodCode();
            }
            $this->static = false;
            $this->balance++;
        } elseif ($type === '}') {
            $this->balance--;
            if ($this->balance < 0) {
                $value = $this->getDelusionMethods() . $value;
            }
        } elseif ($type === T_STATIC) {
            $this->static = true;
        } elseif ($this->static && $type === T_VARIABLE) {
            $this->static = false;
        } elseif ($type === T_START_HEREDOC) {
            $this->inHereDoc = true;
        }

        return $value;
    }

    /**
     * @return string
     */
    protected function getMethodCode()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getDelusionMethods()
    {
        return <<<END
    public function delusionGetInvokesCount(\$method) {}
    public function delusionGetInvokesArguments(\$method) {}
    public function delusionSetBehavior(\$method, \$returns) {}
    public function delusionResetBehavior(\$method) {}
    public function delusionResetAllBehavior() {}
    public function delusionHasCustomBehavior(\$method) {}
    public function delusionResetInvokesCounter(\$method) {}
    public function delusionResetAllInvokesCounter() {}

END;
    }
}
