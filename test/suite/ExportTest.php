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
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Exception\Interfacer\ObjectLoopException;

class ExportTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	public function testSimple()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		
		$json = '{
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
		}';
		
		$test->fill(json_decode($json), $interfacer);
		$this->assertEquals(json_encode(json_decode($json)), $interfacer->toString($test->export($interfacer)));
	}
	
	public function testNotReferenced()
	{
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$test = $model->getObjectInstance();
		
		$json = '{
			"containerForeign":{
				"objOneForeignProp":[1]
			}
		}';
		
		$interfacer->setVerifyReferences(false);
		$test->fill(json_decode($json), $interfacer);
		
		/// must works because interfacer doesn't verify references
		$node = $test->export($interfacer);
		$this->assertEquals(json_encode(json_decode($json)), $interfacer->toString($node));
		
		$thrown = false;
		try {
			$interfacer->setVerifyReferences(true);
			$test->export($interfacer);
		} catch (ExportException $e) {
			$this->assertEquals($e->getStringifiedProperties(), '.containerForeign.objOneForeignProp.0');
			$this->assertEquals(get_class($e->getOriginalException()), NotReferencedValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_REFERENCED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), 'foreign value with model \'Test\Duplicated\ObjectOne\' and id \'1\' not referenced in interfaced object');
			$thrown = true;
		}
		$this->assertTrue($thrown);
	}
	
	/**
	 * @dataProvider thrownExceptionImportData
	 */
	public function testDuplicatedId($root, $dupliProp, $objOneProp, $property, $message)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$stdInterfacer = new StdObjectInterfacer();
		
		$test = $model->getObjectInstance();
		$test->setId($root);
		$obj = $test->initValue('containerOne')->initValue('dupliProp');
		$obj->setId($dupliProp);
		$obj = $test->initValue('containerTwo')->initValue('objOneProp');
		$obj->setId($objOneProp);
		
		$thrown = false;
		try {
			$test->export($stdInterfacer);
		} catch (ExportException $e) {
			$this->assertEquals($e->getStringifiedProperties(), $property);
			$this->assertEquals(get_class($e->getOriginalException()), DuplicatedIdException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::DUPLICATED_ID_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), $message);
			$thrown = true;
		}
		$this->assertTrue($thrown);
	}
	
	public function thrownExceptionImportData()
	{
		return [
			[
				1,
				1,
				null,
				'.containerOne.dupliProp',
				'Duplicated id \'1\''
			],
			[
				null,
				2,
				2,
				'.containerTwo.objOneProp',
				'Duplicated id \'2\''
			]
		];
	}
	
	public function testLoop()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Duplicated');
		$stdInterfacer = new StdObjectInterfacer();
		
		$test = $model->getObjectInstance();
		$obj = $test->initValue('containerTwo')->initValue('objOneProp');
		$test->initValue('containerOne')->setValue('dupliProp', $obj);
		// same instance but NOT nested, so must work
		$test->export($stdInterfacer);
		
		$thrown = false;
		try {
			$test->getValue('containerOne')->setValue('dupliProp', $test);
			// same instance nested, so must NOT work
			$test->export($stdInterfacer);
		} catch (ExportException $e) {
			$this->assertEquals($e->getStringifiedProperties(), '.containerOne.dupliProp');
			$this->assertEquals(get_class($e->getOriginalException()), ObjectLoopException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::OBJECT_LOOP_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), 'Object loop detected, object contain itself');
			$thrown = true;
		}
		$this->assertTrue($thrown);
	}
	
}
