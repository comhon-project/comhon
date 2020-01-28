<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Object\Collection\ObjectCollection;

class ObjectCollectionTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	/**
	 *  @dataProvider buildWithForeignData
	 */
	public function testBuildWithForeign($objectJson, $collectionJson)
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$object = $model->getObjectInstance();
		
		$interfacer->setVerifyReferences(false);
		$object->fill(json_decode($objectJson), $interfacer);
		
		$objectCollection = ObjectCollection::build($object);
		$this->assertEquals($collectionJson, $objectCollection->toString());
	}
	
	public function buildWithForeignData() {
		return [
			[
				'{
					"id":1,
					"containerTwo":{
						"objOneProp":{"id":3}
					},
					"containerMain":{
						"objMainProp":{"id":4}
					}
				}',
				'{"Test\\\\Duplicated":{"1":{"id":1,"containerTwo":{"objOneProp":{"id":3}},"containerMain":{"objMainProp":{"id":4}}},"4":{"id":4},"3":{"id":3}}}'
			],
			[
				'{
					"dupliForeignProp":3,
					"containerForeign":{
						"objOneForeignProp":[1],
						"objMainForeignProp":4
					}
				}',
				'{"Test\\\\Duplicated":{"4":{"id":4},"1":{"id":1},"3":{"id":3}}}'	
			]
				
		];
	}
	
	/**
	 *  @dataProvider buildWithForeignRecursiveData
	 */
	public function testBuildWithForeignRecursive($objectJson)
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$object = $model->getObjectInstance();
		
		$interfacer->setVerifyReferences(false);
		$object->fill(json_decode($objectJson), $interfacer);
		
		$objectCollection = ObjectCollection::build($object->getValue('containerOne'), false);
		$this->assertEquals('{"Test\\\\Duplicated":{"10":{"id":10,"dupliForeignProp":100}}}', $objectCollection->toString());
		
		$objectCollection = ObjectCollection::build($object->getValue('containerOne'), false, true);
		$this->assertEquals(
			'{"Test\\\\Duplicated":{"10":{"id":10,"dupliForeignProp":100},"1000":{"id":1000}}}', 
			$objectCollection->toString()
		);
		
		$objectCollection = ObjectCollection::build($object->getValue('containerOne'), true, true);
		$this->assertEquals(
			'{"Test\\\\Duplicated":{"10":{"id":10,"dupliForeignProp":100},"100":{"id":100,"containerOne":{"dupliProp":{"id":1000}}},"1000":{"id":1000}}}', 
			$objectCollection->toString()
		);
	}
	
	public function buildWithForeignRecursiveData() {
		return [
			[
				'{
					"id":1,
					"containerOne":{
						"dupliProp":{
							"id":10,
							"dupliForeignProp":100
						}
					},
					"containerTwo":{
						"objOneProp":{
							"id":100,
							"containerOne":{
								"dupliProp":{
									"id":1000
								}
							}
						}
					}
				}'
			]
				
		];
	}
	
	public function testBuildWithoutForeign()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$object = $model->getObjectInstance();
		
		$json = '{
			"id":1,
			"dupliForeignProp":3
		}';
		
		$interfacer->setVerifyReferences(false);
		$object->fill(json_decode($json), $interfacer);
		
		$objectCollection = ObjectCollection::build($object);
		$this->assertEquals(
				'{"Test\\\\Duplicated":{"1":{"id":1,"dupliForeignProp":3},"3":{"id":3}}}'
				, $objectCollection->toString()
				);
	}
	
	public function testBuildLoop()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$object = $model->getObjectInstance();
		
		$json = '{
			"containerTwo":{
				"objOneProp":{"id":3}
			}
		}';
		
		$interfacer->setVerifyReferences(false);
		$object->fill(json_decode($json), $interfacer);
		// set loop (reference itself)
		$object->initValue('containerOne')->setValue('dupliProp', $object);
		
		$objectCollection = ObjectCollection::build($object);
		$this->assertEquals(
			'{"Test\\\\Duplicated":{"3":{"id":3}}}'
			, $objectCollection->toString()
		);
	}
	
	public function testIsolatedObject()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest\File');
		$interfacedObject = json_decode('{
		    "name": "Test\\\\MyTest\\\\Isolated",
		    "version": "3.0",
		    "properties": [
		        {
		            "name": "id",
		            "is_id": true,
		            "__inheritance__": "Comhon\\\\Manifest\\\\Property\\\\Integer"
		        }
		    ],
		    "types": [
		        {
		            "name": "One",
		            "properties": [
		                {
		                    "name": "id",
		                    "is_id": true,
		                    "__inheritance__": "Comhon\\\\Manifest\\\\Property\\\\Integer"
		                }
		            ]
		        },
		        {
		            "name": "Two",
		            "properties": [
		                {
		                    "name": "id",
		                    "is_id": true,
		                    "__inheritance__": "Comhon\\\\Manifest\\\\Property\\\\Integer"
		                }
		            ]
		        }
		    ]
		}');
		$object = $model->import($interfacedObject, new StdObjectInterfacer());
		
		// several duplicated id on properties, but must work because types are isolated
		ObjectCollection::build($object);
		$this->assertTrue(true);
	}
	
}
