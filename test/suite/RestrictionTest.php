<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Property\Property;
use Comhon\Model\Restriction\Enum;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Model\Restriction\Restriction;
use Comhon\Model\Restriction\NotNull;
use Comhon\Model\ModelRestrictedArray;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;

class RestrictionTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testFirstNotSatisifedRestriction()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$restrictionOne = new Enum([1, 2]);
		$restrictionTwo = new Enum([1, 3]);
		
		$this->assertSame($restrictionTwo, Restriction::getFirstNotSatisifed([$restrictionOne, $restrictionTwo], 2));
		
		$property = new Property($modelInt, 'hehe', null, false, false, false, true, null, null, [$restrictionOne, $restrictionTwo]);
		$this->assertSame($restrictionTwo, Restriction::getFirstNotSatisifed($property->getRestrictions(), 2));
	}
	
	public function testNotNullRestrictionInstance()
	{
		$notNull = new NotNull();
		$this->assertTrue($notNull->isEqual($notNull));
		$this->assertTrue($notNull->isEqual(new NotNull()));
		$this->assertFalse($notNull->isEqual(new Enum([1, 2])));
		
		$this->assertTrue($notNull->satisfy(1));
		$this->assertFalse($notNull->satisfy(null));
		
		$this->assertEquals('not null value given', $notNull->toMessage(1));
		$this->assertEquals('null value given, value must be not null', $notNull->toMessage(null));
		
		$this->assertEquals('Not null', $notNull->toString());
	}
	
	public function testNotNullRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$restrictions = $model->getProperty('color')->getRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertFalse(isset($restrictions[NotNull::class]));
		
		$restrictions = $model->getProperty('notNullArray')->getRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[NotNull::class]));
		$this->assertInstanceOf(NotNull::class, $restrictions[NotNull::class]);
		$this->assertTrue($restrictions[NotNull::class]->satisfy(1));
		$this->assertFalse($restrictions[NotNull::class]->satisfy(null));
		
		$this->assertInstanceOf(ModelRestrictedArray::class, $model->getProperty('notNullArray')->getModel());
		$restrictions = $model->getProperty('notNullArray')->getModel()->getRestrictions();
		$this->assertCount(2, $restrictions);
		$this->assertTrue(isset($restrictions[NotNull::class]));
		$this->assertInstanceOf(NotNull::class, $restrictions[NotNull::class]);
		$this->assertTrue($restrictions[NotNull::class]->satisfy(1));
		$this->assertFalse($restrictions[NotNull::class]->satisfy(null));
	}
	
	public function testNotNullRestrictionValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->initValue('notNullArray');
		$obj->setValue('notNullArray', $array);
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('notNullArray', null);
	}
	
	public function testNotNullRestrictionArrayValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->initValue('notNullArray');
		$array->pushValue(1.5);
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array->pushValue(null);
	}
	
	public function testNotNullRestrictionAggregation()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$obj = $model->getObjectInstance();
		$obj->initValue('homes');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('homes', null);
	}
	
	public function testNotNullRestrictionForeign()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		$obj->initValue('notNullForeign');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('notNullForeign', null);
	}
}
