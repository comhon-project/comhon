<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class MultipleInheritanceTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testModelWithMultipleInheritance()
	{
		/**
		 * 
		 * @var \Comhon\Model\Model $model
		 */
		$model = ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedFinal');
		$modelParentOne = ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedOne');
		$modelParentTwo = ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedTwo');
		$modelParentThree = ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedThree');
		$modelParentFourth = ModelManager::getInstance()->getInstanceModel('Test\Extends\Multiple\InheritedFourth');
		
		$this->assertCount(2, $model->getParents());
		$this->assertSame($modelParentTwo, $model->getParent());
		$this->assertSame($modelParentTwo, $model->getParent(0));
		$this->assertSame($modelParentFourth, $model->getParent(1));
		$this->assertNull($model->getParent(2));
		$this->assertEquals(['id', 'two', 'three', 'fourth', 'final'], $model->getPropertiesNames());
		
		$this->assertNotNull($model->getSerializationSettings());
		$this->assertEquals($model->getSerializationSettings()->getValue('dir_path'), './data/multiple_inheritance_one_serialization');
		$this->assertSame($model->getSerializationSettings(), $modelParentTwo->getSerializationSettings());
		$this->assertNotSame($model->getSerializationSettings(), $modelParentFourth->getSerializationSettings());
		
		$this->assertTrue($model->isInheritedFrom($modelParentOne));
		$this->assertTrue($model->isInheritedFrom($modelParentTwo));
		$this->assertTrue($model->isInheritedFrom($modelParentThree));
		$this->assertTrue($model->isInheritedFrom($modelParentFourth));
		
		$obj = $model->getObjectInstance();
		$this->assertTrue($obj->isA($model));
		$this->assertTrue($obj->isA($modelParentOne));
		$this->assertTrue($obj->isA($modelParentTwo));
		$this->assertTrue($obj->isA($modelParentThree));
		$this->assertTrue($obj->isA($modelParentFourth));
		$this->assertTrue($obj->isA('Test\Extends\Multiple\InheritedFinal'));
		$this->assertTrue($obj->isA('Test\Extends\Multiple\InheritedOne'));
		$this->assertTrue($obj->isA('Test\Extends\Multiple\InheritedTwo'));
		$this->assertTrue($obj->isA('Test\Extends\Multiple\InheritedThree'));
		$this->assertTrue($obj->isA('Test\Extends\Multiple\InheritedFourth'));
		$this->assertFalse($obj->isA('Test\Test'));
		
		$this->assertSame($modelParentTwo, $model->getFirstSharedIdParentMatch(true));
		$this->assertSame($modelParentOne, $model->getLastSharedIdParentMatch(true));
	}

}
