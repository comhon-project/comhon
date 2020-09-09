<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Model\ModelArray;

class ModelRootTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testGetInstance()
	{
		$modelRoot = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$this->assertEquals('Comhon\Root', $modelRoot->getName());
	}
	
	public function testSameInstance()
	{
		$modelRootOne = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$modelRootTwo = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$this->assertSame($modelRootOne, $modelRootTwo);
	}
	
	public function testInheritance()
	{
		$modelRoot = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable');
		$this->assertSame($modelRoot, $model->getParent());
		
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\File\JsonFile');
		$this->assertSame($modelRoot, $model->getParent()->getParent());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\Id\Simple');
		$this->assertSame($modelRoot, $model->getParent());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid');
		$this->assertSame($modelRoot, $model->getParent()->getParent());
	}
	
	public function testExportUnique()
	{
		$modelRoot = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\Standard');
		$object = $model->getObjectInstance();
		$object->setId('1');
		$interfacer = new AssocArrayInterfacer();
		$this->assertEquals(
				'{"name":"1","inheritance-":"Test\\\\Basic\\\\Standard"}',
				$interfacer->toString($modelRoot->export($object, $interfacer))
		);
	}
	
	public function testExportArray()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$modelArray = new ModelArray($model, false, 'child');
		$array = $modelArray->getObjectInstance();
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\Standard');
		$object = $model->getObjectInstance();
		$object->setId('1');
		$array->pushValue($object);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\NoId');
		$object = $model->getObjectInstance();
		$object->setValue('stringProperty', 'aaa');
		$array->pushValue($object);
		
		$interfacer = new AssocArrayInterfacer();
		$this->assertEquals(
			'[{"name":"1","inheritance-":"Test\\\\Basic\\\\Standard"},{"stringProperty":"aaa","inheritance-":"Test\\\\Basic\\\\NoId"}]',
			$interfacer->toString($modelArray->export($array, $interfacer))
		);
	}
	
	public function testImportUnique()
	{
		$modelRoot = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$interfacer = new AssocArrayInterfacer();
		$object = $modelRoot->import(json_decode('{"name":"1","inheritance-":"Test\\\\Basic\\\\Standard"}', true), $interfacer);
		$this->assertSame(ModelManager::getInstance()->getInstanceModel('Test\Basic\Standard'), $object->getModel());
	}
	
	public function testImportArray()
	{
		
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$modelArray = new ModelArray($model, false, 'child');
		$interfacer = new AssocArrayInterfacer();
		$array = $modelArray->import(
			json_decode('[{"name":"1","inheritance-":"Test\\\\Basic\\\\Standard"},{"stringProperty":"aaa","inheritance-":"Test\\\\Basic\\\\NoId"}]', true),
			$interfacer
		);
		$this->assertSame(ModelManager::getInstance()->getInstanceModel('Test\Basic\Standard'), $array->getValue(0)->getModel());
		$this->assertSame(ModelManager::getInstance()->getInstanceModel('Test\Basic\NoId'), $array->getValue(1)->getModel());
	}
}
