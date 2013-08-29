<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion;

use Delusion\Delusion;
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
        $delusion = Delusion::injection();
        $behavior = $delusion->getClassBehavior('SimpleClassA');
        Assert::identical(0, $behavior->delusionGetInvokesCount('SimpleClassA'));
        Assert::isFalse($behavior->delusionHasCustomBehavior('SimpleClassA'));
        $class = new SimpleClassA();
        Assert::identical('__construct', $class->log[0]);
        Assert::identical(0, $behavior->delusionGetInvokesCount('SimpleClassA'));
        Assert::isFalse($behavior->delusionHasCustomBehavior('SimpleClassA'));
    }
}
