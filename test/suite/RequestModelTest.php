<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Object\ComhonObject;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Object\MissingRequiredValueException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;

class RequestModelTest extends TestCase
{
	private static $data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Request' . DIRECTORY_SEPARATOR;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	/**
	 *
	 * @dataProvider requestData
	 */
	public function testImportExportRequest($json)
	{
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
		
		$interfacedObject = $interfacer->read(self::$data_ad . $json);
		$this->assertTrue(is_array($interfacedObject));
		$obj = $model->import($interfacedObject, $interfacer);
		
		$this->assertSame(
			$interfacedObject,
			$model->export($obj, $interfacer)
		);
	}
	
	public function requestData()
	{
		return [
			[
				'literals.json'
			],
			[
				'settings.json'
			],
			[
				'intermediate.json'
			],
			[
				'complex.json'
			]
		];
	}
	
	public function testIntermediate()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
		
		$interfacedObject = $interfacer->read(self::$data_ad . 'intermediate.json');
		$obj = $model->import($interfacedObject, $interfacer);
		
		$modelPerson = $obj->getValue('models')->getValue(0);
		$collection = $obj->getValue('simpleCollection');
		
		$this->assertInstanceOf(ComhonObject::class, $modelPerson);
		$this->assertSame($modelPerson, $obj->getValue('root'));
		$this->assertSame($modelPerson, $collection->getValue(0)->getValue('node'));
	}
	
	public function testComplex()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
		
		$interfacedObject = $interfacer->read(self::$data_ad . 'complex.json');
		$obj = $model->import($interfacedObject, $interfacer);
		
		$modelPerson = $obj->getValue('tree');
		$modelRooms = $modelPerson->getValue('nodes')->getValue(1)->getValue('nodes')->getValue(0);
		$collection = $obj->getValue('simpleCollection');
		
		$this->assertInstanceOf(ComhonObject::class, $modelPerson);
		$this->assertInstanceOf(ComhonObject::class, $modelRooms);
		$this->assertSame($modelPerson, $collection->getValue(0)->getValue('node'));
		$this->assertSame($modelRooms, $collection->getValue(1)->getValue('node'));
	}
	
	public function testNotExistingModel()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		$obj = $model->getObjectInstance(false);
		
		$thrown = false;
		try {
			$obj->fill(['tree' => ['model' => 'my_model']], new AssocArrayInterfacer());
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.tree.model');
			$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "model 'my_model' doesn't exist");
		}
		$this->assertTrue($thrown);
	}
	
	/** ******************************** import failure ******************************** **/

	/**
	 *
	 * @dataProvider importNotNullData
	 */
	public function testImportNullFailureRequest($interfacedObjectBase, $modelName)
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setVerifyReferences(false);
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$obj = $model->import($interfacedObjectBase, $interfacer);
		
		foreach (array_keys($interfacedObjectBase) as $value) {
			$interfacedObject = $interfacedObjectBase; // copy
			$interfacedObject[$value] = null;
			
			$thrown = false;
			try {
				$model->import($interfacedObject, $interfacer);
			} catch (ImportException $e) {
				$thrown = true;
				$this->assertEquals($e->getStringifiedProperties(), '.'.$value);
				$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
				$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
				$this->assertEquals($e->getOriginalException()->getMessage(), 'null value given, value must be not null');
			}
			$this->assertTrue($thrown, "value '$value' cannot be null");
		}
		$this->assertSame(
			$interfacedObjectBase,
			$model->export($obj, $interfacer)
		);
	}
	
	public function importNotNullData()
	{
		$interfacer = new AssocArrayInterfacer();
		
		$data = $interfacer->read(self::$data_ad . 'required_and_not_null.json');
		$data[] = [
			[
				"limit" => 1,
				"offset" => 0,
				"order" => [],
				"properties" => [],
				"simpleCollection" => [],
				"havingCollection" => [],
				"filter" => 1,
				"root" => 1,
				"models" => [
					[
						"id" => 1,
						"model" => 'Comhon\SqlTable'
					]
				]
			],
			"Comhon\\Request\\Intermediate"
		];
		return $data;
	}
	
	/**
	 *
	 * @dataProvider importRequiredData
	 */
	public function testImportRequiredFailureRequest($interfacedObjectBase, $modelName)
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setVerifyReferences(false);
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$obj = $model->import($interfacedObjectBase, $interfacer);
		
		foreach (array_keys($interfacedObjectBase) as $value) {
			$interfacedObject = $interfacedObjectBase; // copy
			unset($interfacedObject[$value]);
			
			$thrown = false;
			try {
				$model->import($interfacedObject, $interfacer);
			} catch (ImportException $e) {
				$thrown = true;
				$this->assertEquals($e->getStringifiedProperties(), '.');
				$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
				$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
				$message = "missing required value '$value' on loaded comhon object with model '$modelName'";
				$this->assertEquals($e->getOriginalException()->getMessage(), $message);
			}
			$this->assertTrue($thrown, "value '$value' should be required");
		}
		$this->assertSame(
			$interfacedObjectBase,
			$model->export($obj, $interfacer)
		);
	}
	
	public function importRequiredData()
	{
		$interfacer = new AssocArrayInterfacer();
		
		return $interfacer->read(self::$data_ad . 'required_and_not_null.json');
	}
}