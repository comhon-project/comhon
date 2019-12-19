<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Model\ModelForeign;
use Comhon\Model\ModelArray;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\Restriction\Interval;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Model\Restriction\Restriction;

class PropertyEqualityTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testSimpleProperty()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$modelString = ModelManager::getInstance()->getInstanceModel('string');
		$propertyOne = new Property($modelInt, 'hehe', 'hihi', true, true, false, true, 'hoho', true);
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, false, true, 'hoho', true);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		// property is equal even if last param ($isInterfacedAsNodeXml) is different
		// because it doesn't matter on Comhon object instanciation (it is used only during interfacing)
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, false, true, 'hoho', false);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, false, true, 'not_same', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, false, false, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, true, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, false, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', false, true, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'not_same', true, true, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'not_same', 'hihi', true, true, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelString, 'hehe', 'hihi', true, true, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}
	
	public function testAggregationProperty()
	{
		$modelPerson = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$modelMan = ModelManager::getInstance()->getInstanceModel('Test\Person\Man');
		$modelForeignPerson = new ModelForeign(new ModelArray($modelPerson, false, 'child'));
		$modelForeignMan = new ModelForeign(new ModelArray($modelMan, false, 'child'));
		$propertyOne = new AggregationProperty($modelForeignPerson, 'hehe', ['hihi', 'hoho']);
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new AggregationProperty($modelForeignPerson, 'hehe', ['hihi', 'hoho']);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new AggregationProperty($modelForeignPerson, 'hehe', ['hihi', 'not_same']);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new AggregationProperty($modelForeignPerson, 'not_same', ['hihi', 'hoho']);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new AggregationProperty($modelForeignMan, 'hehe', ['hihi', 'hoho']);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelPerson, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}
	
	public function testRestrictedProperty()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$modelFloat = ModelManager::getInstance()->getInstanceModel('float');
		$restrictionOne = new Enum([1, 2]);
		$propertyOne = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 2]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionTwo]);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 666]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionTwo]);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Interval('[1,10]', $modelInt);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionTwo]);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelFloat, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}
	
	
	
	public function testCompareRestrictedProperty()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$restrictionOne = new Enum([1, 2]);
		$restrictionTwo = new Enum([1, 2]);
		$restrictionThree = new Enum([1, 3]);
		
		$this->assertTrue(Restriction::compare([$restrictionOne], [$restrictionTwo]));
		$this->assertFalse(Restriction::compare([$restrictionOne], [$restrictionThree]));
		$this->assertFalse(Restriction::compare([$restrictionOne], [$restrictionTwo, $restrictionThree]));
		// compare same keys, so if same restriction on different keys it return false
		$this->assertFalse(Restriction::compare(['one' => $restrictionOne], ['two' => $restrictionOne]));
		
		// in property constructor, restriction keys are redefined so we don't care about keys
		$propertyOne = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, ['one' => $restrictionOne]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, ['two' => $restrictionOne]);
		$this->assertTrue(Restriction::compare($propertyOne->getRestrictions(), $propertyTwo->getRestrictions()));
		
		$propertyOne = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionTwo]);
		$this->assertTrue(Restriction::compare($propertyOne->getRestrictions(), $propertyTwo->getRestrictions()));
		
		$propertyOne = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionThree]);
		$this->assertFalse(Restriction::compare($propertyOne->getRestrictions(), $propertyTwo->getRestrictions()));
		
		$propertyOne = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne]);
		$propertyTwo = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionTwo, $restrictionThree]);
		$this->assertFalse(Restriction::compare($propertyOne->getRestrictions(), $propertyTwo->getRestrictions()));
	}
	

	public function testPropertyWithModelContainer()
	{
		$modelPerson = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$modelWoman = ModelManager::getInstance()->getInstanceModel('Test\Person\Woman');
		$modelOne = new ModelForeign(new ModelArray($modelPerson, false, 'child'));
		$propertyOne = new Property($modelOne, 'hehe');
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$modelTwo = new ModelForeign(new ModelArray($modelPerson, false, 'child'));
		$propertyTwo = new Property($modelTwo, 'hehe');
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$modelTwo = new ModelForeign(new ModelArray($modelWoman, false, 'child'));
		$propertyTwo = new Property($modelTwo, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$modelTwo = new ModelForeign($modelWoman);
		$propertyTwo = new Property($modelTwo, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$modelTwo = $modelWoman;
		$propertyTwo = new Property($modelTwo, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}
	
	public function testPropertyModelRestrictedArray()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$modelFloat = ModelManager::getInstance()->getInstanceModel('float');
		$restrictionOne = new Enum([1, 2]);
		$propertyOne = new Property(new ModelArray($modelInt, false, 'child', [], [$restrictionOne]), 'hehe');
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new Property(new ModelArray($modelInt, false, 'child', [], [$restrictionOne]), 'hehe');
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 2]);
		$propertyTwo = new Property(new ModelArray($modelInt, false, 'child', [], [$restrictionTwo]), 'hehe');
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 666]);
		$propertyTwo = new Property(new ModelArray($modelInt, false, 'child', [], [$restrictionTwo]), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Interval('[1,10]', $modelInt);
		$propertyTwo = new Property(new ModelArray($modelInt, false, 'child', [], [$restrictionTwo]), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property(new ModelArray($modelFloat, false, 'child', [], [$restrictionOne]), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}

}
