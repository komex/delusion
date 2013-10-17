<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion;

use Delusion\ClassBehavior;
use Unteist\Assert\Assert;
use Unteist\Assert\Matcher\AnyValue;
use Unteist\Assert\Matcher\EqualTo;
use Unteist\TestCase;

/**
 * Class ClassBehaviorTest
 *
 * @package Tests\Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ClassBehaviorTest extends TestCase
{
    /**
     * @var ClassBehavior
     */
    protected $class;

    /**
     * @return array
     */
    public function dpSimpleMethodBehavior()
    {
        return [
            ['method', 'returns'],
            ['method', ['s1', true, .5]],
            ['method2', new \SplFixedArray()],
            [
                'method3',
                function () {
                    return 6;
                },
                6
            ],
        ];
    }

    /**
     * @param string $method
     * @param mixed $returns
     * @param mixed $expected
     *
     * @dataProvider dpSimpleMethodBehavior
     */
    public function testSimpleMethodBehavior($method, $returns, $expected = null)
    {
        Assert::isFalse($this->class->delusionHasCustomBehavior($method));
        $this->class->delusionSetCustomBehavior($method, $returns);
        Assert::isTrue($this->class->delusionHasCustomBehavior($method));
        if ($expected === null) {
            $expected = $returns;
        }
        Assert::equals($expected, $this->class->delusionGetCustomBehavior($method, []));
    }

    /**
     * Reset behavior for one method.
     */
    public function testResetMethodBehavior()
    {
        $this->class->delusionSetCustomBehavior('method1', 1);
        $this->class->delusionSetCustomBehavior('method2', 2);
        $this->class->delusionResetCustomBehavior('method1');
        Assert::isFalse($this->class->delusionHasCustomBehavior('method1'));
        Assert::isTrue($this->class->delusionHasCustomBehavior('method2'));
    }

    /**
     * Reset all behavior.
     */
    public function testResetAllBehavior()
    {
        $this->class->delusionSetCustomBehavior('method1', 1);
        $this->class->delusionSetCustomBehavior('method2', 2);
        $this->class->delusionResetAllCustomBehavior();
        Assert::isFalse($this->class->delusionHasCustomBehavior('method1'));
        Assert::isFalse($this->class->delusionHasCustomBehavior('method2'));
    }

    public function testEmptyInvokes()
    {
        Assert::identical(0, $this->class->delusionGetInvokesCount('method'));
    }

    /**
     * Test successful register invokes static method.
     */
    public function testRegisterInvokes()
    {
        $data_provider = [
            ['method', ['arg1'], 1],
            ['method', [5, true], 2],
            ['method2', ['arg2'], 1],
        ];
        foreach ($data_provider as $data) {
            list($method, $arguments, $count) = $data;
            $this->class->delusionRegisterInvoke($method, $arguments);
            $invokes_arguments = $this->class->delusionGetInvokesArguments($method);
            Assert::identical($count, $this->class->delusionGetInvokesCount($method));
            Assert::that($invokes_arguments, new AnyValue(new EqualTo($arguments)));
        }
    }

    /**
     * Reset behavior for one method.
     */
    public function testResetMethodInvokes()
    {
        Assert::identical(0, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(0, $this->class->delusionGetInvokesCount('method2'));
        $this->class->delusionRegisterInvoke('method1', ['arg1']);
        $this->class->delusionRegisterInvoke('method1', ['arg1']);
        $this->class->delusionRegisterInvoke('method2', ['arg1']);
        Assert::identical(2, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(1, $this->class->delusionGetInvokesCount('method2'));
        $this->class->delusionResetInvokesCounter('method1');
        Assert::identical(0, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(1, $this->class->delusionGetInvokesCount('method2'));
    }

    /**
     * Reset behavior for one method.
     */
    public function testResetAllInvokes()
    {
        Assert::identical(0, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(0, $this->class->delusionGetInvokesCount('method2'));
        $this->class->delusionRegisterInvoke('method1', ['arg1']);
        $this->class->delusionRegisterInvoke('method1', ['arg1']);
        $this->class->delusionRegisterInvoke('method2', ['arg1']);
        Assert::identical(2, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(1, $this->class->delusionGetInvokesCount('method2'));
        $this->class->delusionResetAllInvokesCounter();
        Assert::identical(0, $this->class->delusionGetInvokesCount('method1'));
        Assert::identical(0, $this->class->delusionGetInvokesCount('method2'));
    }

    /**
     * Create a new instance for each test.
     *
     * @beforeTest
     */
    public function setUp()
    {
        $this->class = new ClassBehavior();
    }
}
