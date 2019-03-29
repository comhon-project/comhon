<?php
use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\NotDefinedModelException;
use Comhon\Object\Collection\MainObjectCollection;

class LoadingModelTest extends TestCase
{
	
	protected function setUp()
	{
		ModelManager::resetSingleton();
	}
	
	
	
	public function testResetSingleton()
	{
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
		ModelManager::resetSingleton();
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Test'));
	}
	
	public function testPropertyWithMalformedModel()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\FatherMalformed');
		try {
			// model 'childMalformed' is not loaded yet and must failed when loading
			$model->getProperty('child')->getModel()->getModel();
		} catch (NotDefinedModelException $e) {
			$hasThrownEx = true;
			$this->assertEquals('model Test\ChildMalformed\wrong-type doesn\'t exist', $e->getMessage());
			
			// model 'childMalformed' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$childModel = $model->getProperty('child')->getModel()->getModel();
		}
		
		// should failed before 
		$this->assertTrue($hasThrownEx);
	}
	
	public function testMalformedModel()
	{
		$hasThrownEx = false;
		try {
			// model 'childMalformed' must failed when loading
			$model = ModelManager::getInstance()->getInstanceModel('Test\ChildMalformed');
		} catch (NotDefinedModelException $e) {
			$hasThrownEx = true;
			$this->assertEquals('model Test\ChildMalformed\wrong-type doesn\'t exist', $e->getMessage());
			
			// model 'childMalformed' shouldn't be tagged as loaded and must failed again when loading
			$this->expectException(NotDefinedModelException::class);
			$model = ModelManager::getInstance()->getInstanceModel('Test\ChildMalformed');
		}
		
		// should failed before
		$this->assertTrue($hasThrownEx);
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
