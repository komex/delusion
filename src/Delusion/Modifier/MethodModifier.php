<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Delusion\Modifier;

use Delusion\Delusion;

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
        } elseif ($type === '{') {
            $value = $this->openBracket($value);
        } elseif ($type === '}') {
            $value = $this->closeBracket($value);
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
     * Get method controller code.
     *
     * @return string
     */
    protected function getMethodCode()
    {
        if ($this->static) {
            $condition = '';
        } else {
            $condition = sprintf(
                'if ($this->delusionHasCustomBehavior(%1$s)) return $this->delusionReturn(%1$s, func_get_args());',
                '__FUNCTION__'
            );
        }

        return $condition;
    }

    /**
     * Get code for control class.
     *
     * @return string
     */
    protected function getDelusionMethods()
    {
        $prefix = Delusion::injection()->getPrefix();

        return <<<END
    protected \${$prefix}invokes = [];
    protected \${$prefix}behavior = [];
    private function delusionReturn(\$method, array \$args) {
        \$return = \${$prefix}behavior[\$method];
        if (is_callable(\$return)) {
            return \$return(\$args);
        } else {
            return \$return;
        }
    }
    public function delusionGetInvokesCount(\$method) {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }
    public function delusionGetInvokesArguments(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}invokes) ? \$this->{$prefix}invokes[\$method] : [];
    }
    public function delusionSetBehavior(\$method, \$returns) {
        \$this->{$prefix}returns[\$method] = \$returns;
    }
    public function delusionHasCustomBehavior(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}returns);
    }
    public function delusionResetBehavior(\$method) {
        unset(\$this->{$prefix}returns[\$method]);
    }
    public function delusionResetAllBehavior() {
        \$this->{$prefix}returns = [];
    }
    public function delusionResetInvokesCounter(\$method) {
        unset(\$this->{$prefix}invokes[\$method]);
    }
    public function delusionResetAllInvokesCounter() {
        \$this->{$prefix}invokes = [];
    }

END;
    }

    /**
     * Received open bracket.
     *
     * @param string $value Tag value
     *
     * @return string
     */
    private function openBracket($value)
    {
        if ($this->balance === 0) {
            $value .= $this->getMethodCode();
        }
        $this->static = false;
        $this->balance++;

        return $value;
    }

    /**
     * Received close bracket.
     *
     * @param string $value Tag value
     *
     * @return string
     */
    private function closeBracket($value)
    {
        $this->balance--;
        if ($this->balance < 0) {
            $value = $this->getDelusionMethods() . $value;
        }

        return $value;
    }
}
