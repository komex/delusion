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
            ['Delusion'],
            $this->delusion->getBlackList(),
            'Invalid default values in black list'
        );
        $this->delusion->setBlackList(['SomeClassName1', 'SomeClassName2', '\\SomeClassName1', 'Delusion']);
        Assert::equals(
            ['SomeClassName1', 'SomeClassName2', 'Delusion'],
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
        Assert::count(4, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('AddToBlackList')));
        $this->delusion->addToBlackList('Delusion');
        Assert::count(4, $this->delusion->getBlackList());
        $this->delusion->addToBlackList('AddToBlackList2');
        Assert::count(5, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('AddToBlackList2')));
    }

    /**
     * @depends testAddToBlackList
     */
    public function testRemoveFromBlackList()
    {
        $this->delusion->removeFromBlackList('\\SomeClassName1');
        Assert::count(4, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new Not(new AnyValue(new IdenticalTo('SomeClassName1'))));
        $this->delusion->removeFromBlackList('Delusion');
        Assert::count(4, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('Delusion')));
    }

    /**
     * @depends testRemoveFromBlackList
     */
    public function testRemoveFromBlackListByPrefix()
    {
        $this->delusion->removeFromBlackList('AddToBlackList');
        Assert::count(2, $this->delusion->getBlackList());
        Assert::that($this->delusion->getBlackList(), new Not(new AnyValue(new IdenticalTo('AddToBlackList2'))));
        Assert::equals(
            ['SomeClassName2', 'Delusion'],
            $this->delusion->getBlackList()
        );
    }

    /**
     * @depends testSetBlackList
     */
    public function testRemoveFromBlackListDefaults()
    {
        $this->delusion->removeFromBlackList('Delusion');
        Assert::that($this->delusion->getBlackList(), new AnyValue(new IdenticalTo('Delusion')));
    }

    public function testSetWhiteList()
    {
        Assert::isEmpty($this->delusion->getWhiteList(), 'By default, white list must be empty');
        Assert::typeOf('array', $this->delusion->getWhiteList(), 'White list must be an array');
        $this->delusion->setWhiteList(['SomeClassName1', 'SomeClassName2', '\\SomeClassName1']);
        Assert::equals(['SomeClassName1', 'SomeClassName2'], $this->delusion->getWhiteList());
        $this->delusion->setWhiteList(['Delusion']);
        Assert::isEmpty(
            $this->delusion->getWhiteList(),
            '"Delusion" classes may not be sets to white list'
        );
    }

    public function testAddToWhiteList()
    {
        $this->delusion->addToWhiteList('SomeClassName1');
        Assert::equals(['SomeClassName1'], $this->delusion->getWhiteList());
        $this->delusion->addToWhiteList('Delusion');
        Assert::equals(['SomeClassName1'], $this->delusion->getWhiteList());
    }

    public function testRemoveFromWhiteList()
    {
        $this->delusion->setWhiteList(
            ['Some\\Class\\Name1', '\\Some\\Class\\Name2', 'Another\\Class\\Name1', 'Another\\Class\\Name2']
        );
        Assert::count(4, $this->delusion->getWhiteList());
        $this->delusion->removeFromWhiteList('\\Another\\Class\\Name1');
        Assert::count(3, $this->delusion->getWhiteList());
        Assert::that($this->delusion->getWhiteList(), new Not(new AnyValue(new IdenticalTo('Another\\Class\\Name1'))));
    }

    /**
     * @depends testRemoveFromWhiteList
     */
    public function testRemoveFromWhiteListByPrefix()
    {
        $this->delusion->removeFromWhiteList('\\Some\\Cl');
        Assert::equals(['Another\\Class\\Name2'], $this->delusion->getWhiteList());
    }
}
