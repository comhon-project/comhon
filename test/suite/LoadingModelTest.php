<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Model\NotDefinedModelException;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Exception\Manifest\ManifestException;

class LoadingModelTest extends TestCase
{
	
	protected function setUp()
	{
		Config::setLoadPath(Data::$config);
		ModelManager::resetSingleton();
	}
	
	
	
	public function testResetSingleton()
	{
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
		ModelManager::getInstance()->getInstanceModel('Test\Test');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
		ModelManager::resetSingleton();
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
	}
	
	/**
	 * test loading malformed manifest
	 */
	public function testMalformedManifest()
	{
		$this->expectException(ManifestException::class);
		$this->expectExceptionMessage(
			'manifest malformed :' . PHP_EOL
			. "manifest file not found or malformed '"
			. dirname(__DIR__)
			. "/config/../manifests/manifest/Load/Malformed/manifest."
			. config::getInstance()->getManifestFormat() 
			. "'"
		);
		ModelManager::getInstance()->getInstanceModel('Test\Load\Malformed');
	}
	
	/**
	 * test loading manifest with local type that has a property with model not defined
	 */
	public function testLocalPropertyWithNotDefinedType()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\Load\LocalTypeNotDefinedProperty');
		try {
			// model 'malformedChild' is not loaded yet and must failed when loading
			$model->getProperty('malformedProperty')->getModel()->getProperty('malformedPropertyInLocal')->getModel();
		} catch (NotDefinedModelException $e) {
			$hasThrownEx = true;
			$this->assertEquals('model Test\Load\LocalTypeNotDefinedProperty\wrong-type-in-local doesn\'t exist', $e->getMessage());
			
			// model 'malformedChild' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$model->getProperty('malformedProperty')->getModel()->getProperty('malformedPropertyInLocal')->getModel();
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test loading manifest with type, defined in another manifest,
	 * that has a property with model not defined
	 */
	public function testComplexPropertyWithNotDefinedType()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\Load\LocalTypeNotDefinedProperty');
		try {
			// model 'malformedChild' is not loaded yet and must failed when loading
			$model->getProperty('malformedChild')->getModel()->getProperty('malformedProperty')->getModel();
		} catch (NotDefinedModelException $e) {
			$hasThrownEx = true;
			$this->assertEquals('model Test\Load\NotDefinedProperty\wrong-type doesn\'t exist', $e->getMessage());
			
			// model 'malformedChild' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$model->getProperty('malformedChild')->getModel()->getProperty('malformedProperty')->getModel();
		}
		
		// should failed before 
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test loading manifest with a property with model not defined
	 */
	public function testNotDefinedType()
	{
		$hasThrownEx = false;
		try {
			// model 'malformedChild' must failed when loading
			$model = ModelManager::getInstance()->getInstanceModel('Test\Load\NotDefinedProperty');
			$model->getProperty('malformedProperty')->getModel();
		} catch (NotDefinedModelException $e) {
			$hasThrownEx = true;
			$this->assertEquals('model Test\Load\NotDefinedProperty\wrong-type doesn\'t exist', $e->getMessage());
			
			// model 'malformedChild' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$model = ModelManager::getInstance()->getInstanceModel('Test\Load\NotDefinedProperty');
			$model->getProperty('malformedProperty')->getModel();
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test manifest loading with a local type that is defined in another manifest
	 */
	public function testLocalTypeDefinedInAnotheranifest() {
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Load\Local'));
		$modelParent = ModelManager::getInstance()->getInstanceModel('Test\Load\LocalOnOtherManifest');
		
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Load\Local'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Load\Local'));
		
		$modelLocal = ModelManager::getInstance()->getInstanceModel('Test\Load\Local');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Load\Local'));
		$this->assertTrue($modelLocal->isLoaded());
		
		$this->assertSame($modelParent->getProperty('localDistant')->getModel(), $modelLocal);
		$this->assertEquals($modelLocal->getPropertiesNames(), ['local']);
	}
	
	/**
	 * load model on sub directory (not contained in autoloading root directory)
	 */
	public function testLoadModelNotRoot()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal\Recursive');
		$this->assertEquals('Test\Test\PersonLocal\Recursive', $model->getName());
		$this->assertEquals(['id', 'firstName', 'anotherObjectWithIdAndMore'], $model->getPropertiesNames());
	}
	
	/**
	 * load model discribed inside a manifest in local types
	 */
	public function testLoadLocalModel()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal\Recursive\ObjectWithIdAndMore');
		$this->assertEquals('Test\Test\PersonLocal\Recursive\ObjectWithIdAndMore', $model->getName());
		$this->assertEquals(['plop', 'plop2', 'plop3'], $model->getPropertiesNames());
	}
	
	/**
	 * load model discribed inside a manifest in local types
	 */
	public function testLoadSerializableModel()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
		$this->assertEquals('Test\TestDb', $model->getName());
		$this->assertFalse($model->getSqlTableSettings()->getValue('database')->isLoaded());
	}

}
