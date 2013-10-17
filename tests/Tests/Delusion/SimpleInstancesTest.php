<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion;

use Delusion\ClassBehavior;
use Delusion\Delusion;
use Delusion\DelusionInterface;
use Tests\Delusion\Resources\SimpleClassA;
use Unteist\Assert\Assert;
use Unteist\TestCase;

/**
 * Class InstancesTest
 *
 * @package Tests\Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SimpleInstancesTest extends TestCase
{
    /**
     * @var ClassBehavior
     */
    private $behavior;

    /**
     * @beforeCase
     */
    public function setUpBeforeCase()
    {
        $delusion = Delusion::injection();
        $this->behavior = $delusion->getClassBehavior('Tests\\Delusion\\Resources\\SimpleClassA');
    }

    /**
     * @afterTest
     */
    public function reset()
    {
        $this->behavior->delusionResetAllCustomBehavior();
        $this->behavior->delusionResetAllInvokesCounter();
    }

    /**
     * @return array
     */
    public function dpOriginalBehavior()
    {
        return [
            [3, 4, 9, 11],
            [4, 5, 10, 12],
            [10, 11, 16, 18],
        ];
    }

    /**
     * @param int $num
     * @param int $public
     * @param int $protected
     * @param int $private
     *
     * @dataProvider dpOriginalBehavior
     */
    public function testOriginalBehavior($num, $public, $protected, $private)
    {
        $class = new SimpleClassA();
        Assert::count(1, $class->log);
        Assert::identical($public, $class->publicMethod($num));
        Assert::identical($protected, $class->callProtected($num));
        Assert::identical($private, $class->callPrivate($num));
        Assert::count(6, $class->log);
        Assert::equals(
            [
                '__construct',
                'publicMethod',
                'callProtected',
                'protectedMethod',
                'callPrivate',
                'privateMethod',
            ],
            $class->log
        );
    }

    /**
     * Test instance behavior not affected to statics classes behavior.
     */
    public function testInstancesNotAffectingStatics()
    {
        Assert::identical(0, $this->behavior->delusionGetInvokesCount('__construct'));
        Assert::isFalse($this->behavior->delusionHasCustomBehavior('__construct'));
        $class = new SimpleClassA();
        Assert::identical('__construct', $class->log[0]);
        Assert::identical(0, $this->behavior->delusionGetInvokesCount('__construct'));
        Assert::isFalse($this->behavior->delusionHasCustomBehavior('__construct'));
    }

    /**
     * Test working with constructor.
     */
    public function testConstructor()
    {
        $this->behavior->delusionSetCustomBehavior('__construct', null);

        Assert::isTrue($this->behavior->delusionHasCustomBehavior('__construct'));
        $class = new SimpleClassA();
        Assert::isEmpty($class->log);

        $this->behavior->delusionResetCustomBehavior('__construct');
        Assert::isFalse($this->behavior->delusionHasCustomBehavior('__construct'));
        $class = new SimpleClassA();
        Assert::count(1, $class->log);
    }

    /**
     * Test the behavior of the methods with the established global guidelines.
     */
    public function testCustomDefaults()
    {
        $this->behavior->delusionSetCustomBehavior('publicMethod', 'default value');

        $class2 = new SimpleClassA();
        Assert::identical('default value', $class2->publicMethod());
        $class3 = new SimpleClassA();
        Assert::identical('default value', $class3->publicMethod());

        $this->behavior->delusionResetCustomBehavior('publicMethod');

        $class4 = new SimpleClassA();
        Assert::identical(3, $class4->publicMethod());
    }

    /**
     * Test behavior priority.
     */
    public function testBehaviorPriority()
    {
        /** @var DelusionInterface|SimpleClassA $class */
        $class = new SimpleClassA();
        Assert::identical(3, $class->publicMethod());
        $this->behavior->delusionSetCustomBehavior('publicMethod', 'default value');
        Assert::identical('default value', $class->publicMethod());
        $class->delusionSetCustomBehavior('publicMethod', 'instance');
        Assert::identical('instance', $class->publicMethod());
    }
}
