<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Tests\Delusion;

use Delusion\ClassBehavior;

/**
 * Class ClassBehaviorTest
 *
 * @package Tests\Delusion
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class ClassBehaviorTest extends \PHPUnit_Framework_TestCase
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
                }
            ],
        ];
    }

    /**
     * @param string $method
     * @param mixed $returns
     *
     * @dataProvider dpSimpleMethodBehavior
     */
    public function testSimpleMethodBehavior($method, $returns)
    {
        $this->assertFalse($this->class->delusionHasCustomBehavior($method));
        $this->class->delusionSetBehavior($method, $returns);
        $this->assertTrue($this->class->delusionHasCustomBehavior($method));
        $this->assertEquals($returns, $this->class->getCustomBehavior($method));
    }

    public function testResetMethodBehavior()
    {
        $this->class->delusionSetBehavior('method1', 1);
        $this->class->delusionSetBehavior('method2', 2);
        $this->class->delusionResetBehavior('method1');
        $this->assertFalse($this->class->delusionHasCustomBehavior('method1'));
        $this->assertTrue($this->class->delusionHasCustomBehavior('method2'));
    }

    public function testResetAllBehavior()
    {
        $this->class->delusionSetBehavior('method1', 1);
        $this->class->delusionSetBehavior('method2', 2);
        $this->class->delusionResetAllBehavior();
        $this->assertFalse($this->class->delusionHasCustomBehavior('method1'));
        $this->assertFalse($this->class->delusionHasCustomBehavior('method2'));
    }

    /**
     * Create a new instance for each test.
     */
    protected function setUp()
    {
        $this->class = new ClassBehavior();
    }
}
