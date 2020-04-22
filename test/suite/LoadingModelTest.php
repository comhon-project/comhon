<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Model\NotDefinedModelException;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Model\AlreadyUsedModelNameException;

class LoadingModelTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
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
			"manifest file not found or malformed '"
			. dirname(__DIR__)
			. "/config/../manifests/test/manifest/Load/Malformed/manifest."
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
			$this->assertEquals('manifest not found for model Test\Load\LocalTypeNotDefinedProperty\wrong-type-in-local', $e->getMessage());
			
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
			$this->assertEquals('manifest not found for model Test\Load\NotDefinedProperty\wrong-type', $e->getMessage());
			
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
			$this->assertEquals('manifest not found for model Test\Load\NotDefinedProperty\wrong-type', $e->getMessage());
			
			// model 'malformedChild' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$model = ModelManager::getInstance()->getInstanceModel('Test\Load\NotDefinedProperty');
			$model->getProperty('malformedProperty')->getModel();
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test loading manifest with same model name.
	 * Test\Load\Duplicate\Local is defined in a manifest and in local type of Test\Load\Duplicate
	 */
	public function testDuplicatedModelName()
	{
		$this->expectException(AlreadyUsedModelNameException::class);
		$this->expectExceptionMessage('model Test\Load\Duplicate\Local already used');
		
		// Test\Load\Duplicate\Local\MyLocal is a local type
		// load Test\Load\Duplicate\Local\MyLocal and instanciate Test\Load\Duplicate\Local and set manifest parser
		ModelManager::getInstance()->getInstanceModel('Test\Load\Duplicate\Local\MyLocal');
		
		// load Test\Load\Duplicate and use instanciated Test\Load\Duplicate\Local and failed because a manifest parser is already set
		ModelManager::getInstance()->getInstanceModel('Test\Load\Duplicate');
	}
	
	/**
	 * test loading manifest with loop extends
	 */
	public function testLoopExtends()
	{
		$hasThrownEx = false;
		try {
			// model 'Test\Extends\Loop\LoopOne' must failed when loading
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Loop\LoopOne');
		} catch (ComhonException $e) {
			$hasThrownEx = true;
			$this->assertEquals('loop detected in model inheritance : Test\Extends\Loop\LoopThree and Test\Extends\Loop\LoopOne', $e->getMessage());
			
			// model 'Test\Extends\Loop\LoopOne' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(ComhonException::class);
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Loop\LoopOne');
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test loading manifest with loop extends (in local types)
	 */
	public function testLocalLoopExtends()
	{
		$hasThrownEx = false;
		try {
			// model 'Test\Extends\Loop\LoopOne' must failed when loading
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Loop\LoopOne\LocalLoopTwo');
		} catch (ComhonException $e) {
			$hasThrownEx = true;
			$this->assertEquals('loop detected in model inheritance : Test\Extends\Loop\LoopOne\LocalLoopOne and Test\Extends\Loop\LoopOne\LocalLoopTwo', $e->getMessage());
			
			// model 'Test\Extends\Loop\LoopOne' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(ComhonException::class);
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Loop\LoopOne\LocalLoopTwo');
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
	}
	
	/**
	 * test conflict properties on extended models (not same stype)
	 */
	public function testConflictPropertyExtendsOne()
	{
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('Inheritance conflict on property "integerProperty" on model "Test\Extends\Conflict\One"');
		ModelManager::getInstance()->getInstanceModel('Test\Extends\Conflict\One');
	}
	
	/**
	 * test conflict properties on extended models (with and without restriction)
	 */
	public function testConflictPropertyExtendsTwo()
	{
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('Inheritance conflict on property "integerProperty" on model "Test\Extends\Conflict\Two"');
		ModelManager::getInstance()->getInstanceModel('Test\Extends\Conflict\Two');
	}
	
	/**
	 * test conflict properties on extended models (with different restriction)
	 */
	public function testConflictPropertyExtendsThree()
	{
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('Inheritance conflict on property "integerProperty" on model "Test\Extends\Conflict\Three"');
		ModelManager::getInstance()->getInstanceModel('Test\Extends\Conflict\Three');
	}
	
	/**
	 * test conflict properties on extended models (multiple conflict from extended classes)
	 */
	public function testConflictPropertyExtendsFour()
	{
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('Multiple inheritance conflict on property "integerProperty" on model "Test\Extends\Conflict\Four"');
		ModelManager::getInstance()->getInstanceModel('Test\Extends\Conflict\Four');
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
	
	/**
	 * load model discribed inside a manifest in local types.
	 * load principale model first
	 */
	public function testLoadLocalExtendsItself()
	{
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid\Itself'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid\Itself\Itself'));
		
		$modelItself = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself');
		$this->assertEquals('Test\Extends\Valid\Itself', $modelItself->getName());
		$this->assertEquals(['id', 'stringProperty'], $modelItself->getPropertiesNames());
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid\Itself\Itself'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Extends\Valid\Itself\Itself'));
		
		$modelItselfItself = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself\Itself');
		$this->assertEquals('Test\Extends\Valid\Itself\Itself', $modelItselfItself->getName());
		$this->assertEquals(['id', 'stringProperty', 'floatProperty'], $modelItselfItself->getPropertiesNames());
		
		$this->assertSame($modelItselfItself->getParent(), $modelItself);
	}
	
	/**
	 * load model discribed inside a manifest in local types.
	 * load local model first
	 */
	public function testLoadLocalExtendsItselfLocalFirst()
	{
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid\Itself'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid\Itself\Itself'));
		
		$modelItselfItself = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself\Itself');
		$this->assertEquals('Test\Extends\Valid\Itself\Itself', $modelItselfItself->getName());
		$this->assertEquals(['id', 'stringProperty', 'floatProperty'], $modelItselfItself->getPropertiesNames());
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Extends\Valid\Itself'));
		
		$modelItself = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself');
		$this->assertEquals('Test\Extends\Valid\Itself', $modelItself->getName());
		$this->assertEquals(['id', 'stringProperty'], $modelItself->getPropertiesNames());
		
		$this->assertSame($modelItselfItself->getParent(), $modelItself);
	}
	
	
	
	/**
	 * load model discribed inside a manifest in local types.
	 * load local model first
	 */
	public function testLoadExtends()
	{
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Extends\Valid'));
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid');
		$this->assertEquals('Test\Extends\Valid', $model->getName());
		$this->assertEquals(['id', 'stringProperty', 'floatProperty'], $model->getPropertiesNames());
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Extends\Valid'));
		
		$this->assertSame(
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself'), 
			$model->getParent()
		);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\One');
		$this->assertEquals('Test\Extends\Valid\One', $model->getName());
		$this->assertEquals(['id', 'stringProperty', 'floatProperty', 'integerProperty', 'booleanProperty'], $model->getPropertiesNames());
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Extends\Valid\One'));
		
		$this->assertSame(
			ModelManager::getInstance()->getInstanceModel('Test\Extends\Valid\Itself\Itself'),
			$model->getParent()->getParent()
		);
	}
}
