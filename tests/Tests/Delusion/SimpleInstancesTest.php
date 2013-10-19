<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion;

use Delusion\Configurator;
use Delusion\Delusion;
use Delusion\Suggestible;
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
     * @var Suggestible
     */
    private $suggest;

    /**
     * @beforeCase
     */
    public function setUpBeforeCase()
    {
        $this->suggest = Delusion::injection()->getSuggest('Tests\\Delusion\\Resources\\SimpleClassA');
    }

    /**
     * @afterTest
     */
    public function reset()
    {
        Configurator::resetAllInvokes($this->suggest);
        Configurator::resetAllCustomBehavior($this->suggest);
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
        Assert::identical(0, Configurator::getInvokesCount($this->suggest, '__construct'));
        Assert::isFalse(Configurator::hasCustomBehavior($this->suggest, '__construct'));
        $class = new SimpleClassA();
        Assert::identical('__construct', $class->log[0]);
        Assert::identical(0, Configurator::getInvokesCount($this->suggest, '__construct'));
        Assert::isFalse(Configurator::hasCustomBehavior($this->suggest, '__construct'));
    }

    /**
     * Test working with constructor.
     */
    public function testConstructor()
    {
        Configurator::setCustomBehavior($this->suggest, '__construct', null);

        Assert::isTrue(Configurator::hasCustomBehavior($this->suggest, '__construct'));
        $class = new SimpleClassA();
        Assert::isEmpty($class->log);

        Configurator::resetCustomBehavior($this->suggest, '__construct');
        Assert::isFalse(Configurator::hasCustomBehavior($this->suggest, '__construct'));
        $class = new SimpleClassA();
        Assert::count(1, $class->log);
    }
}
