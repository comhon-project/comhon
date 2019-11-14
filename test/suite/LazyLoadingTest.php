<?php

use PHPUnit\Framework\TestCase;
use Comhon\Object\Config\Config;
use Test\Comhon\Data;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class LazyLoadingTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		ModelManager::resetSingleton();
	}
	
	/**
	 * test model that has properties with models that must not be loaded during : 
	 * - first model intanciation
	 * - import/export with null or empty values
	 * 
	 * @dataProvider LazyLoadingData
	 */
	public function testLazyLoading($interfacedObject, $interfacer, $verifValues)
	{
		$personModel = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$person = $personModel->getObjectInstance();
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
		
		$person->fill($interfacedObject, $interfacer);
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
		
		foreach ($verifValues as $propertyName) {
			$this->assertTrue($person->hasValue($propertyName));
		}
		
		$person->export($interfacer);
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
	}
	
	public function LazyLoadingData() {
		return [
			[ // test without values json
				json_decode('{"firstName": "hehe","father": 1}'),
				new StdObjectInterfacer(),
				[]
			],
			[ // test without values xml
				simplexml_load_string('<root firstName="hehe"><father>1</father></root>'),
				new XMLInterfacer(),
				[]
			],
			[ // test wit null values json
				json_decode('{"birthPlace": null,"homes": null}'),
				new StdObjectInterfacer(),
				['birthPlace','homes']
			],
			[ // test wit null values xml
				simplexml_load_string('<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><birthPlace xsi:nil="true"/><homes xsi:nil="true"/></root>'),
				new XMLInterfacer(),
				['birthPlace','homes']
			],
			[ // test with empty array values json
				json_decode('{"firstName": "hehe2","father": 1,"homes": []}'),
				new StdObjectInterfacer(),
				['homes']
			],
			[ // test with empty array values xml
				simplexml_load_string('<root firstName="hehe2"><father>1</father><homes/></root>'),
				new XMLInterfacer(),
				['homes']
			],
		];
	}
	
	/**
	 * test model that has properties with models that must not be loaded during :
	 */
	public function testLazyLoadingManifest()
	{
		// load model that have properties that reference external models  
		ModelManager::getInstance()->getInstanceModel('Test\Basic\ExternalReference');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Basic\Id'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Basic\Id\ObjectContainer'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id'));
		
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Basic\NoId\ObjectContainer'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModel('Test\Basic\NoId'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\NoId\ObjectContainer'));
		
		// load model defined by local type in manifest (principal manifest model must not be loaded not loaded)
		ModelManager::getInstance()->getInstanceModel('Test\Basic\Id\ObjectContainer');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id\ObjectContainer'));
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Basic\Id\Object'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id\Object'));
		
		// load principal model defined in manifest with "local" model already loaded
		ModelManager::getInstance()->getInstanceModel('Test\Basic\Id');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id\Object'));
		
		// load last local model
		ModelManager::getInstance()->getInstanceModel('Test\Basic\Id\Object');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\Id\Object'));
		
		// other tests
		ModelManager::getInstance()->getInstanceModel('Test\Basic\NoId');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModel('Test\Basic\NoId\ObjectContainer'));
		$this->assertFalse(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\NoId\ObjectContainer'));
		ModelManager::getInstance()->getInstanceModel('Test\Basic\NoId\ObjectContainer');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\NoId\ObjectContainer'));
		ModelManager::getInstance()->getInstanceModel('Test\Basic\NoId\Object');
		$this->assertTrue(ModelManager::getInstance()->hasInstanceModelLoaded('Test\Basic\NoId\Object'));
	}
	
}
