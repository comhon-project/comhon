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
		$this->assertEquals($model->getSerializationSettings()->getValue('staticPath'), './data/multiple_inheritance_one_serialization');
		$this->assertSame($model->getSerializationSettings(), $modelParentTwo->getSerializationSettings());
		$this->assertNotSame($model->getSerializationSettings(), $modelParentFourth->getSerializationSettings());
		
		$this->assertTrue($model->isInheritedFrom($modelParentOne));
		$this->assertTrue($model->isInheritedFrom($modelParentTwo));
		$this->assertTrue($model->isInheritedFrom($modelParentThree));
		$this->assertTrue($model->isInheritedFrom($modelParentFourth));
		
		$this->assertSame($modelParentTwo, $model->getFirstMainParentMatch(true));
		$this->assertSame($modelParentOne, $model->getLastMainParentMatch(true));
	}

}
