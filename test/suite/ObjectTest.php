<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\Collection\MainObjectCollection;

class ObjectTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	public function testResetObject()
	{
		// comhon object
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$object = $model->getObjectInstance();
		$this->assertCount(0, $object->getValues());
		$this->assertTrue($object->isLoaded());
		$object->setValue('naturalNumber', 10);
		$this->assertCount(1, $object->getValues());
		
		$object->reset();
		$this->assertFalse($object->isLoaded());
		$this->assertCount(0, $object->getValues());
		
		// comhon array
		$array = $object->initValue('notEmptyArray', false);
		$this->assertCount(0, $array->getValues());
		$array->pushValue('aaa');
		$array->setIsLoaded(true);
		$this->assertTrue($array->isLoaded());
		$this->assertCount(1, $array->getValues());
		
		$array->reset();
		$this->assertFalse($array->isLoaded());
		$this->assertCount(0, $array->getValues());
	}
	
	
	public function testLoadObjectInvalidId()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest');
		// 'id property as a regex restriction' and cannot contain '-'
		// loadObject must return null and must not throw exception
		$obj = $model->loadObject('test-person');
		$this->assertNull($obj);
	}
	
}
