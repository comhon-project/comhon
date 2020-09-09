<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Exception\Interfacer\DuplicatedIdException;
use Comhon\Exception\ConstantException;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Exception\Interfacer\NotReferencedValueException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class ImportTest extends TestCase
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
	 * test a fill function (with interfacer merge type set to Interfacer::MERGE by default).
	 * 
	 * @dataProvider importData
	 */
	public function testSimpleFillObject($baseJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		$this->assertEquals(Interfacer::MERGE, $stdInterfacer->getMergeType());
		
		$test->fill(json_decode($baseJson), $stdInterfacer);
		$this->assertSame($test->getValue('containerTwo')->getValue('objOneProp'), $test->getValue('dupliForeignProp'));
		$this->assertSame($test->getValue('containerMain')->getValue('objMainProp'), $test->getValue('containerForeign')->getValue('objMainForeignProp'));
		$this->assertSame($test, $test->getValue('containerForeign')->getValue('objOneForeignProp')->getValue(0));
		$this->assertNotSame($test->getValue('containerOne')->getValue('objTwoProp'), $test->getValue('containerTwo')->getValue('objOneProp'));
		$this->assertTrue($test->isLoaded());
		$this->assertTrue($test->getValue('containerTwo')->getValue('objOneProp')->isLoaded());
		$this->assertTrue($test->getValue('containerOne')->getValue('objTwoProp')->isLoaded());
		$this->assertTrue($test->getValue('containerOne')->getValue('dupliProp')->isLoaded());
		$this->assertTrue($test->getValue('containerMain')->getValue('objMainProp')->isLoaded());
	}
	
	/**
	 * test a fill function called twice with same interfaced object (with interfacer merge type set to Interfacer::MERGE)
	 *
	 * @dataProvider importData
	 */
	public function testFillObjectAgain($baseJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$test->fill(json_decode($baseJson), $stdInterfacer);
		$this->assertSame($test->getValue('containerTwo')->getValue('objOneProp'), $test->getValue('dupliForeignProp'));
		$this->assertNotSame($test->getValue('containerOne')->getValue('objTwoProp'), $test->getValue('containerTwo')->getValue('objOneProp'));
		$this->assertSame($test, $test->getValue('containerForeign')->getValue('objOneForeignProp')->getValue(0));
		$this->assertTrue($test->isLoaded());
		$this->assertTrue($test->getValue('containerTwo')->getValue('objOneProp')->isLoaded());
		$this->assertTrue($test->getValue('containerOne')->getValue('objTwoProp')->isLoaded());
		$this->assertTrue($test->getValue('containerOne')->getValue('dupliProp')->isLoaded());
		
	}
	
	/**
	 * test a fill function called twice (with interfacer merge type set to Interfacer::MERGE).
	 * second fill call import interfaced object without some values
	 */
	public function testFillObjectAgainPartialOne()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$interfacer = new AssocArrayInterfacer();
		
		$array = [
			'containerOne' => [
				'dupliProp' => ['id' => 2],
				'objTwoProp' => ['id' => 3]
			]
		];
		// first fill
		$test->fill($array, $interfacer);
		$dupliProp = $test->getValue('containerOne')->getValue('dupliProp');
		
		unset($array['containerOne']['objTwoProp']);
		$test->fill($array, $interfacer);
		$this->assertTrue($test->getValue('containerOne')->hasValue('dupliProp'));
		$this->assertSame($test->getValue('containerOne')->getValue('dupliProp'), $dupliProp);
		$this->assertFalse($test->getValue('containerOne')->hasValue('objTwoProp'));
	}
	
	/**
	 * test a fill function called twice (with interfacer merge type set to Interfacer::MERGE).
	 * second fill call import interfaced object without some values
	 *
	 * @dataProvider importData
	 */
	public function testFillObjectAgainPartialTwo($baseJson, $partialJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$test->fill(json_decode($partialJson), $stdInterfacer);
		$this->assertTrue($test->getValue('containerTwo')->hasValue('objOneProp'));
		$this->assertSame($test->getValue('containerTwo')->getValue('objOneProp'), $test->getValue('dupliForeignProp'));
		$this->assertTrue($test->getValue('containerMain')->hasValue('objMainProp'));
		$this->assertSame($test->getValue('containerMain')->getValue('objMainProp'), $test->getValue('containerForeign')->getValue('objMainForeignProp'));
		$this->assertTrue($test->getValue('dupliForeignProp')->isLoaded());
	}
	
	/**
	 * test a fill function called twice (with interfacer merge type set to Interfacer::MERGE).
	 * second fill call import interfaced object without some values
	 *
	 * @dataProvider importData
	 */
	public function testFillObjectAgainPartialOverwrite($baseJson, $partialJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$stdInterfacer->setMergeType(Interfacer::OVERWRITE);
		$stdInterfacer->setVerifyReferences(false); // some foreign values are not referenced so we modify setting
		$test->fill(json_decode($partialJson), $stdInterfacer);
		$this->assertFalse($test->getValue('dupliForeignProp')->isLoaded());
		$this->assertNull($test->getValue('containerMain'));
		$this->assertTrue($test->getValue('containerForeign')->getValue('objMainForeignProp')->isLoaded());
		$this->assertNull($test->getValue('containerTwo'));
		$this->assertTrue($test->getValue('containerForeign')->getValue('objOneForeignProp')->getValue(0)->isLoaded());
		$this->assertSame($test, $test->getValue('containerForeign')->getValue('objOneForeignProp')->getValue(0));
	}
	
	/**
	 * test import after fill with objects that have main models
	 *
	 * @dataProvider importData
	 */
	public function testImportAfterFillObject($baseJson, $partialJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$stdInterfacer->setVerifyReferences(false); // some foreign values are not referenced so we modify setting
		$test = $model->import(json_decode($partialJson), $stdInterfacer);
		$this->assertFalse($test->getValue('dupliForeignProp')->isLoaded());
		$this->assertTrue($test->getValue('containerForeign')->getValue('objMainForeignProp')->isLoaded());
	}
	
	/**
	 * test a fill function called twice (with interfacer merge type set to Interfacer::OVERWRITE).
	 * second fill has not referenced foreign values so  : 
	 * - exception must be thrown for values with NOT main model.
	 * - exception must NOT be thrown for values with main model.
	 * (values are present in first imported object and are not present in new imported object)
	 *
	 * @dataProvider importData
	 */
	public function testNotReferencedDuringFill($baseJson, $partialJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$interfacedObj = json_decode($partialJson);
		$stdInterfacer->setMergeType(Interfacer::OVERWRITE);
		try {
			$hasThrownEx = false;
			$test->fill($interfacedObj, $stdInterfacer);
		} catch (ImportException $e) {
			$hasThrownEx = true;
			$this->asserttrue($e->getOriginalException() instanceof NotReferencedValueException);
			$this->assertEquals($e->getCode(), ConstantException::NOT_REFERENCED_VALUE_EXCEPTION);
		}
		// should failed before
		$this->assertTrue($hasThrownEx);
		
		unset($interfacedObj->dupliForeignProp);
		// after unset 'dupliForeignProp', only foreign value 'objMainForeignProp' is not referenced
		// and it has a main model so import must NOT throw exception
		// because values with main model doesn't need to be referenced in current object
		$test->fill($interfacedObj, $stdInterfacer);
	}
	
	/**
	 * test import (after fill) that has not referenced foreign values so : 
	 * - exception must be thrown for values with NOT main model.
	 * - exception must NOT be thrown for values with main model.
	 * (values are present in first imported object and are not present in new imported object)
	 *
	 * @dataProvider importData
	 */
	public function testNotReferencedDuringImport($baseJson, $partialJson)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		// first fill
		$test->fill(json_decode($baseJson), $stdInterfacer);
		
		$interfacedObj = json_decode($partialJson);
		try {
			$hasThrownEx = false;
			$test = $model->import($interfacedObj, $stdInterfacer);
		} catch (ImportException $e) {
			$hasThrownEx = true;
			$this->asserttrue($e->getOriginalException() instanceof NotReferencedValueException);
			$this->assertEquals($e->getCode(), ConstantException::NOT_REFERENCED_VALUE_EXCEPTION);
		}
		// should failed before
		$this->assertTrue($hasThrownEx);
		
		unset($interfacedObj->dupliForeignProp);
		// after unset 'dupliForeignProp', only foreign value 'objMainForeignProp' is not referenced 
		// and it has a main model so import must NOT throw exception
		// because values with main model doesn't need to be referenced in current object
		$test = $model->import($interfacedObj, $stdInterfacer);
	}
	
	public function importData()
	{
		return [
			[
				'{
					"id":1,
					"dupliForeignProp":3,
					"containerOne":{
						"dupliProp":{"id":2},
						"objTwoProp":{"id":3}
					},
					"containerTwo":{
						"objOneProp":{"id":3}
					},
					"containerMain":{
						"objMainProp":{"id":4}
					},
					"containerForeign":{
						"objOneForeignProp":[1],
						"objMainForeignProp":4
					}
				}',
				'{
					"id":1,
					"dupliForeignProp":3,
					"containerOne":{
						"dupliProp":{"id":2},
						"objTwoProp":{"id":3}
					},
					"containerForeign":{
						"objOneForeignProp":[1],
						"objMainForeignProp":4
					}
				}'
			]
		];
	}
	
	/**
	 * @dataProvider thrownExceptionImportData
	 */
	public function testThrownExceptionImport($modelName, $jsonValue, $stringProperties, $exClass, $exCode, $exMessage)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		try {
			$test->fill(json_decode($jsonValue), $stdInterfacer);
		} catch (ImportException $e) {
			$this->assertEquals($e->getStringifiedProperties(), $stringProperties);
			$this->assertEquals(get_class($e->getOriginalException()), $exClass);
			$this->assertEquals($e->getOriginalException()->getCode(), $exCode);
			$this->assertEquals($e->getOriginalException()->getMessage(), $exMessage);
		}
	}
	
	public function thrownExceptionImportData()
	{
		return [
			[
				'Test\Test',
				'{"objectContainer":{"person":{"recursiveLocal":{"firstName":true}}}}',
				'.objectContainer.person.recursiveLocal.firstName',
				UnexpectedValueTypeException::class,
				ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
				'value must be a string, boolean \'true\' given',
			],
			[
				'Test\Duplicated',
				'{"id":1,"containerOne":{"dupliProp":{"id":1}}}',
				'.containerOne.dupliProp',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'1\'',
			],
			[
				'Test\Duplicated',
				'{"id":2,"containerTwo":{"objOneProp":{"id":2}}}',
				'.containerTwo.objOneProp',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'2\'',
			],
			[
				'Test\Duplicated',
				'{"id":3,"containerOne":{"dupliProp":{"id":4}},"containerTwo":{"objOneProp":{"id":4}}}',
				'.containerTwo.objOneProp',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'4\'',
			],
			[
				'Test\Duplicated',
				'{"id":5,"containerForeign":{"objOneForeignProp":[6]}}',
				'.containerForeign.objOneForeignProp.0',
				NotReferencedValueException::class,
				ConstantException::NOT_REFERENCED_VALUE_EXCEPTION,
				'foreign value with model \'Test\Duplicated\ObjectOne\' and id \'6\' not referenced in interfaced object',
			]
		];
	}
	
	
	
	/**
	 * @dataProvider importNullValuesData
	 */
	public function testImportNullValues($modelName, $xmlString)
	{
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$interfacer = new XMLInterfacer();
		$interfacer->setVerifyReferences(false);
		$object = $interfacer->import($interfacer->fromString($xmlString), $model);
		$this->assertEquals($xmlString, $interfacer->toString($object->export($interfacer)));
	}
	
	public function importNullValuesData()
	{
		return [
			[
				'Test\TestDb',
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="default"><objectsWithId><objectWithId xsi:nil="true"/></objectsWithId></root>',
			],
			[
				'Test\TestDb',
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="default"><foreignObjects><foreignObject xsi:nil="true"/><foreignObject>two</foreignObject></foreignObjects></root>',
			],
			[
				'Test\TestDb',
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" defaultValue="default"><date xsi:nil="true"/><foreignObjects xsi:nil="true"/></root>',
			],
			[
				'Test\TestAssociativeArray',
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><names><name key-="one">my_name</name><name key-="two" xsi:nil="true"/></names><emails><email xsi:nil="true"/><email>aaa@gmail.com</email></emails></root>',
			]
		];
	}

}
