<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Exception\Object\AbstractObjectException;
use Comhon\Object\ComhonObject;
use Comhon\Exception\ConstantException;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Exception\Interfacer\AbstractObjectExportException;

class AbstractModelTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	/**
	 * 
	 * @dataProvider comhonAbstractModelData
	 */
	public function testComhonAbstractModel($modelName)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$this->assertTrue($model->isAbstract());
	}
	
	public function comhonAbstractModelData() {
		return [
				['Comhon\File'],
				['Comhon\Logic\Formula'],
				['Comhon\Logic\Having\Formula'],
				['Comhon\Logic\Having\Literal'],
				['Comhon\Logic\Simple\Formula'],
				['Comhon\Logic\Simple\Literal'],
				['Comhon\Logic\Simple\Literal\Numeric'],
				['Comhon\Logic\Simple\Literal\Set'],
				['Comhon\Logic\Simple\Literal\Set\Numeric'],
				['Test\Basic\Id\Simple']
		];
	}
	
	public function testInstanciateLoadedObject()
	{
		new ComhonObject('Test\Basic\Id\Simple', false);
		
		$this->expectException(AbstractObjectException::class);
		$this->expectExceptionCode(ConstantException::ABSTRACT_OBJECT_EXCEPTION);
		$this->expectExceptionMessage('model \'Test\Basic\Id\Simple\' is abstract. Objects with abstract model cannot be flagged as loaded');
		
		new ComhonObject('Test\Basic\Id\Simple', true);
	}
	
	public function testInstanciateLoadedObjectFromModel()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\Id\Simple');
		$model->getObjectInstance(false);
		
		$this->expectException(AbstractObjectException::class);
		$this->expectExceptionMessage('model \'Test\Basic\Id\Simple\' is abstract. Objects with abstract model cannot be flagged as loaded');
		
		$model->getObjectInstance(true);
	}
	
	public function testSetIsLoadedObject()
	{
		$obj = new ComhonObject('Test\Basic\Id\Simple', false);
		
		$this->expectException(AbstractObjectException::class);
		$this->expectExceptionMessage('model \'Test\Basic\Id\Simple\' is abstract. Objects with abstract model cannot be flagged as loaded');
		
		$obj->setIsLoaded(true);
	}
	
	public function testCastObject()
	{
		$modelNotAbstract = ModelManager::getInstance()->getInstanceModel('Test\Extends\Abstract\IsNotAbstract');
		$modelAbstract = ModelManager::getInstance()->getInstanceModel('Test\Extends\Abstract\IsAbstract');
		$obj = new ComhonObject($modelNotAbstract, false);
		$obj->cast($modelNotAbstract);
		$obj->cast($modelAbstract);
		
		$obj = new ComhonObject($modelNotAbstract, true);
		$obj->cast($modelNotAbstract);
		
		$this->expectException(AbstractObjectException::class);
		$this->expectExceptionMessage('model \'Test\Extends\Abstract\IsAbstract\' is abstract. Objects with abstract model cannot be flagged as loaded');
		$obj->cast($modelAbstract);
	}
	
	public function testImportInvalid()
	{
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Having\Formula');
		
		$this->expectException(ImportException::class);
		$this->expectExceptionMessage('model \'Comhon\Logic\Having\Formula\' is abstract. Objects with abstract model cannot be flagged as loaded');
		$interfacer->import([], $model);
	}
	
	public function testImportInvalidTwo()
	{
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		
		$this->expectException(ImportException::class);
		$this->expectExceptionMessage('model \'Comhon\Logic\Simple\Formula\' is abstract. Objects with abstract model cannot be flagged as loaded');
		$interfacer->import(['simple_collection' => [[Interfacer::COMPLEX_ID_KEY => 1, Interfacer::INHERITANCE_KEY => 'Comhon\Logic\Simple\Formula']]], $model);
	}
	
	public function testImportValid()
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setVerifyReferences(false);
		$interfacer->setValidate(false);
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		
		// filter property is foreign so object is NOT loaded and may be instanciated
		$obj = $interfacer->import(['filter' => [Interfacer::COMPLEX_ID_KEY => 2, Interfacer::INHERITANCE_KEY => 'Comhon\Logic\Simple\Formula']], $model);
		$this->assertFalse($obj->getValue('filter')->isLoaded());
	}
	
	public function testExportInvalid() {
		
		$interfacer = new AssocArrayInterfacer();
		
		$modelAbstract = ModelManager::getInstance()->getInstanceModel('Test\Extends\Abstract\IsAbstract');
		$obj = new ComhonObject($modelAbstract, false);
		
		$thrown = false;
		try {
			$obj->export($interfacer);
		} catch (ExportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), AbstractObjectExportException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::ABSTRACT_OBJECT_EXPORT_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "model 'Test\Extends\Abstract\IsAbstract' is abstract. abstract model can't be exported");
		}
		$this->assertTrue($thrown);
	}
}
