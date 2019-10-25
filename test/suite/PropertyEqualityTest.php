<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Model\ModelForeign;
use Comhon\Model\ModelArray;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\Property\RestrictedProperty;
use Comhon\Model\ModelRestrictedArray;
use Comhon\Model\Restriction\Interval;

class PropertyEqualityTest extends TestCase
{
	
	public function testSimpleProperty()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$modelString = ModelManager::getInstance()->getInstanceModel('string');
		$propertyOne = new Property($modelInt, 'hehe', 'hihi', true, true, true, 'hoho', true);
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, true, 'hoho', true);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		// property is equal even if last param ($isInterfacedAsNodeXml) is different
		// because it doesn't matter on Comhon object instanciation (it is used only during interfacing)
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, true, 'hoho', false);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, true, 'not_same', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, true, false, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', true, false, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'hihi', false, true, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe', 'not_same', true, true, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'not_same', 'hihi', true, true, true, 'hoho', true);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelString, 'hehe', 'hihi', true, true, true, 'hoho', true);
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
		$propertyOne = new RestrictedProperty($modelInt, 'hehe', $restrictionOne);
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new RestrictedProperty($modelInt, 'hehe', $restrictionOne);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 2]);
		$propertyTwo = new RestrictedProperty($modelInt, 'hehe', $restrictionTwo);
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 666]);
		$propertyTwo = new RestrictedProperty($modelInt, 'hehe', $restrictionTwo);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Interval('[1,10]', $modelInt);
		$propertyTwo = new RestrictedProperty($modelInt, 'hehe', $restrictionTwo);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new RestrictedProperty($modelFloat, 'hehe', $restrictionOne);
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
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
		$propertyOne = new Property(new ModelRestrictedArray($modelInt, $restrictionOne, false, 'child'), 'hehe');
		
		$this->assertTrue($propertyOne->isEqual($propertyOne));
		
		$propertyTwo = new Property(new ModelRestrictedArray($modelInt, $restrictionOne, false, 'child'), 'hehe');
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 2]);
		$propertyTwo = new Property(new ModelRestrictedArray($modelInt, $restrictionTwo, false, 'child'), 'hehe');
		$this->assertTrue($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Enum([1, 666]);
		$propertyTwo = new Property(new ModelRestrictedArray($modelInt, $restrictionTwo, false, 'child'), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$restrictionTwo = new Interval('[1,10]', $modelInt);
		$propertyTwo = new Property(new ModelRestrictedArray($modelInt, $restrictionTwo, false, 'child'), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property(new ModelRestrictedArray($modelFloat, $restrictionOne, false, 'child'), 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
		
		$propertyTwo = new Property($modelInt, 'hehe');
		$this->assertFalse($propertyOne->isEqual($propertyTwo));
	}

}
