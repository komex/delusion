<?php
/**
 * This file is a part of delusion project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Delusion;

use Delusion\Delusion;
use Unteist\Assert\Assert;
use Unteist\Assert\Matcher\AnyValue;
use Unteist\Assert\Matcher\IdenticalTo;
use Unteist\Assert\Matcher\Not;
use Unteist\TestCase;

/**
 * Class DelusionTest
 *
 * @package Tests\Delusion
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class DelusionTest extends TestCase
{
    /**
     * @var Delusion
     */
    protected $delusion;

    /**
     * @beforeCase
     */
    public function beforeCase()
    {
        $this->delusion = Delusion::injection();
    }

    public function testSetBlackList()
    {
        Assert::equals(
            ['Delusion', 'TokenReflection'],
            $this->delusion->getBlackList(),
            'Invalid default values in black list'
        );
        $this->delusion->setBlackList(['SomeClassName1', 'SomeClassName2', '\\SomeClassName1']);
        Assert::equals(
            ['SomeClassName1', 'SomeClassName2', 'Delusion', 'TokenReflection'],
            $this->delusion->getBlackList(),
            'Black list setter must always appends a default classes and removes duplicates'
        );
    }

    /**
     * @depends testSetBlackList
     */
    public function testAddToBlackList()
    {
        Assert::that($this->delusion->getBlackList(), new Not(new AnyValue(new IdenticalTo('AddToBlackList'))));
        $this->delusion->addToBlackList('AddToBlackList');
        Assert::count(5, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('AddToBlackList')));
        $this->delusion->addToBlackList('Delusion');
        Assert::count(5, $this->delusion->getBlackList());
        $this->delusion->addToBlackList('AddToBlackList2');
        Assert::count(6, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('AddToBlackList2')));
    }

    /**
     * @depends testAddToBlackList
     */
    public function testRemoveFromBlackList()
    {
        $this->delusion->removeFromBlackList('SomeClassName1');
        Assert::count(5, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new Not(new AnyValue(new IdenticalTo('SomeClassName1'))));
    }

    /**
     * @depends testRemoveFromBlackList
     */
    public function testRemoveFromBlackListByPrefix()
    {
        $this->delusion->removeFromBlackList('AddToBlackList');
        Assert::count(3, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new Not(new AnyValue(new IdenticalTo('AddToBlackList2'))));
    }

    /**
     * @depends testSetBlackList
     */
    public function testRemoveFromBlackListDefaults()
    {
        $this->delusion->removeFromBlackList('Delusion');
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('Delusion')));
    }
}
