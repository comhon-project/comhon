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

class ImportExportTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	public function testNoMergeFillObject()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		
		$jsonValue = '{
			"id":1,
			"objectOneProperty":{"id":3}
		}';
		
		$stdInterfacer->setMergeType(Interfacer::NO_MERGE);
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		
		// during fillObject Interfacer::NO_MERGE is not persistent
		// so it is implicitely transformed at the beginning to Interfacer::OVERWRITE and reset to Interfacer::NO_MERGE at the end
		// we verify that at the end we still have Interfacer::NO_MERGE
		$this->assertEquals(Interfacer::NO_MERGE, $stdInterfacer->getMergeType());
		
		$this->assertEquals(1, $test->getId());
		$this->assertTrue($test->hasValue('objectOneProperty'));
		$this->assertEquals(3, $test->getValue('objectOneProperty')->getId());
		
		$jsonValue = '{
			"id":1
		}';
		
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		$this->assertEquals(1, $test->getId());
		$this->assertFalse($test->hasValue('objectOneProperty'));
	}
	
	public function testForeignImport()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		$stdInterfacer = new StdObjectInterfacer();
		$this->assertEquals(Interfacer::MERGE, $stdInterfacer->getMergeType());
		
		$jsonValue = '{
			"id":1,
			"duplicatedForeignProperty":3,
			"duplicatedProperty":{"id":2},
			"objectOneProperty":{"id":3},
			"objectTwoProperty":{"id":3},
			"objectOneForeignProperty":1,
			"objectMainForeignProperty":4,
			"objectMainProperty":{"id":4}
		}';
		
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		$this->assertSame($test->getValue('objectOneProperty'), $test->getValue('duplicatedForeignProperty'));
		$this->assertSame($test->getValue('objectMainProperty'), $test->getValue('objectMainForeignProperty'));
		$this->assertSame($test, $test->getValue('objectOneForeignProperty'));
		$this->assertNotSame($test->getValue('objectTwoProperty'), $test->getValue('objectOneProperty'));
		$this->assertTrue($test->isLoaded());
		$this->assertTrue($test->getValue('objectOneProperty')->isLoaded());
		$this->assertTrue($test->getValue('objectTwoProperty')->isLoaded());
		$this->assertTrue($test->getValue('duplicatedProperty')->isLoaded());
		$this->assertTrue($test->getValue('objectMainProperty')->isLoaded());
		
		// test fill same object again with same interfaced object
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		$this->assertSame($test->getValue('objectOneProperty'), $test->getValue('duplicatedForeignProperty'));
		$this->assertNotSame($test->getValue('objectTwoProperty'), $test->getValue('objectOneProperty'));
		$this->assertSame($test, $test->getValue('objectOneForeignProperty'));
		$this->assertTrue($test->isLoaded());
		$this->assertTrue($test->getValue('objectOneProperty')->isLoaded());
		$this->assertTrue($test->getValue('objectTwoProperty')->isLoaded());
		$this->assertTrue($test->getValue('duplicatedProperty')->isLoaded());
		
		// test with foreign values that doesn't have real reference in interfaced object
		$jsonValue = '{
			"id":1,
			"duplicatedForeignProperty":3,
			"duplicatedProperty":{"id":2},
			"objectTwoProperty":{"id":3},
			"objectOneForeignProperty":1,
			"objectMainForeignProperty":4
		}';
		
		// test fill same object again with new interfaced object
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		$this->assertTrue($test->hasValue('objectOneProperty'));
		$this->assertSame($test->getValue('objectOneProperty'), $test->getValue('duplicatedForeignProperty'));
		$this->assertTrue($test->hasValue('objectMainProperty'));
		$this->assertSame($test->getValue('objectMainProperty'), $test->getValue('objectMainForeignProperty'));
		$this->assertTrue($test->getValue('duplicatedForeignProperty')->isLoaded());
		
		// test fill same object again with new interfaced object and merge type OVERWRITE
		$stdInterfacer->setMergeType(Interfacer::OVERWRITE);
		$test->fill(json_decode($jsonValue), $stdInterfacer);
		$this->assertNull($test->getValue('objectMainProperty'));
		$this->assertTrue($test->getValue('objectMainForeignProperty')->isLoaded());
		$this->assertNull($test->getValue('objectOneProperty'));
		$this->assertTrue($test->getValue('objectOneForeignProperty')->isLoaded());
		$this->assertSame($test, $test->getValue('objectOneForeignProperty'));
		
		// test import object with main model that has been previously added in MainObjectCollection
		$test = $model->import(json_decode($jsonValue), $stdInterfacer);
		$this->assertFalse($test->getValue('duplicatedForeignProperty')->isLoaded());
		$this->assertNull($test->getValue('objectMainProperty'));
		$this->assertTrue($test->getValue('objectMainForeignProperty')->isLoaded());
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
				'{"id":1,"duplicatedProperty":{"id":1}}',
				'.duplicatedProperty',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'1\'',
			],
			[
				'Test\Duplicated',
				'{"id":2,"objectOneProperty":{"id":2}}',
				'.objectOneProperty',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'2\'',
			],
			[
				'Test\Duplicated',
				'{"id":3,"duplicatedProperty":{"id":4},"objectOneProperty":{"id":4}}',
				'.objectOneProperty',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				'Duplicated id \'4\'',
			]
		];
	}

}
