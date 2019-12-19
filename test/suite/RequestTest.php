<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Object\ComhonObject;
use Comhon\Exception\Interfacer\ImportException;

class RequestTest extends TestCase
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
		$modelHouse = $obj->getValue('models')->getValue(1);
		$collection = $obj->getValue('simpleCollection');
		
		$this->assertInstanceOf(ComhonObject::class, $modelPerson);
		$this->assertInstanceOf(ComhonObject::class, $modelHouse);
		$this->assertSame($modelPerson, $obj->getValue('root'));
		$this->assertSame($modelPerson, $collection->getValue(0)->getValue('node'));
		$this->assertSame($modelHouse, $collection->getValue(1)->getValue('node'));
	}
	
	public function testComplex()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
		
		$interfacedObject = $interfacer->read(self::$data_ad . 'complex.json');
		try {
			$obj = $model->import($interfacedObject, $interfacer);
		} catch (ImportException $e) {
			var_dump($e->getStringifiedProperties());
			var_dump($e->getMessage());
			var_dump($e->getOriginalException()->getTraceAsString());
		}
		
		$modelPerson = $obj->getValue('tree');
		$modelRooms = $modelPerson->getValue('nodes')->getValue(1)->getValue('nodes')->getValue(0);
		$collection = $obj->getValue('simpleCollection');
		
		$this->assertInstanceOf(ComhonObject::class, $modelPerson);
		$this->assertInstanceOf(ComhonObject::class, $modelRooms);
		$this->assertSame($modelPerson, $collection->getValue(0)->getValue('node'));
		$this->assertSame($modelRooms, $collection->getValue(1)->getValue('node'));
	}

}
