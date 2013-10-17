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
     * @var bool
     */
    protected $waitStaticTarget = false;

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
            $value = $this->openBracket($value);
        } elseif ($type === '}') {
            $value = $this->closeBracket($value);
        } elseif ($type === T_START_HEREDOC) {
            $this->inHereDoc = true;
        } else {
            $this->recognizeStatic($type);
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
        $condition = '';
        if ($this->static) {
            $prefix = Delusion::injection()->getPrefix();
            $class = '$' . $prefix . 'class';
            $condition .= $class . ' = \\Delusion\\Delusion::injection()->getClassBehavior(__CLASS__); ';
        } else {
            $class = '$this';
        }
        $condition .= sprintf('%s->delusionRegisterInvoke(__FUNCTION__, func_get_args()); ', $class);
        $condition .= sprintf('if (%s->delusionHasCustomBehavior(__FUNCTION__)) ', $class);
        $condition .= sprintf('return %s->delusionGetCustomBehavior(__FUNCTION__, func_get_args());', $class);

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
    protected \${$prefix}returns = [];
    public function delusionGetInvokesCount(\$method) {
        return count(\$this->delusionGetInvokesArguments(\$method));
    }
    public function delusionGetInvokesArguments(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}invokes) ? \$this->{$prefix}invokes[\$method] : [];
    }
    public function delusionResetInvokesCounter(\$method) {
        unset(\$this->{$prefix}invokes[\$method]);
    }
    public function delusionResetAllInvokesCounter() {
        \$this->{$prefix}invokes = [];
    }
    public function delusionRegisterInvoke(\$method, array \$arguments) {
        if (empty(\$this->{$prefix}invokes[\$method])) {
            \$this->{$prefix}invokes[\$method] = [];
        }
        array_push(\$this->{$prefix}invokes[\$method], \$arguments);
    }
    public function delusionGetCustomBehavior(\$method, array \$args) {
        \$return = \${$prefix}returns[\$method];
        if (is_callable(\$return)) {
            \$return = call_user_func_array(\$return, \$args);
        }
        return \$return;
    }
    public function delusionHasCustomBehavior(\$method) {
        return array_key_exists(\$method, \$this->{$prefix}returns);
    }
    public function delusionSetCustomBehavior(\$method, \$returns) {
        \$this->{$prefix}returns[\$method] = \$returns;
    }
    public function delusionResetCustomBehavior(\$method) {
        unset(\$this->{$prefix}returns[\$method]);
    }
    public function delusionResetAllCustomBehavior() {
        \$this->{$prefix}returns = [];
    }

END;
    }

    /**
     * Recognize if method is static.
     *
     * @param string|int $type Tag value
     */
    private function recognizeStatic($type)
    {
        if ($type === T_STATIC) {
            $this->waitStaticTarget = true;
        } elseif ($this->waitStaticTarget) {
            if ($type === T_FUNCTION) {
                $this->static = true;
                $this->waitStaticTarget = false;
            } elseif ($type === T_VARIABLE) {
                $this->static = false;
                $this->waitStaticTarget = false;
            }
        }
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
