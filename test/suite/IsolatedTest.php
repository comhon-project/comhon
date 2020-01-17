<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\DuplicatedIdException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\NotReferencedValueException;
use Comhon\Exception\Interfacer\ExportException;

class IsolatedTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testIsolated()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$this->assertFalse($model->getProperty('foreignObject')->isIsolated());
		$this->assertTrue($model->getProperty('containerIsolated')->isIsolated());
		$this->assertFalse($model->getProperty('containerArrayIsolated')->getModel()->isIsolatedElement());
		$this->assertTrue($model->getProperty('containerArrayIsolated')->getModel()->getModel()->isIsolatedElement());
	}
	
	public function testImportExportValid()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		$interfacedObject = [
			'id' => 0,
			'foreignObject' => 'name_one',
			'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
			'containerIsolated' => [
				'id' => 1,
				'foreignObject' => 'name_one',
				'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
				'containerArrayIsolated' => [[
					[
						'id' => 2,
						'foreignObject' => 'name_one',
						'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
					],
					[
						'id' => 3,
						'foreignObject' => 'name_one',
						'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
					]
				]]
			]
		];
		$test = $model->import($interfacedObject, $interfacer);
		$this->assertSame($test->getValue('foreignObject'), $test->getValue('objects')->getValue(0)->getValue(0));
		$this->assertSame(
			$test->getValue('containerIsolated')->getValue('foreignObject'),
			$test->getValue('containerIsolated')->getValue('objects')->getValue(0)->getValue(0)
		);
		
		// first level - second level : not same instance due to isolated status 
		$this->assertEquals(
			$test->getValue('foreignObject')->getId(),
			$test->getValue('containerIsolated')->getValue('foreignObject')->getId()
		);
		$this->assertEquals(
			$test->getValue('foreignObject')->getModel()->getName(),
			$test->getValue('containerIsolated')->getValue('foreignObject')->getModel()->getName()
		);
		$this->assertNotSame($test->getValue('foreignObject'), $test->getValue('containerIsolated')->getValue('foreignObject'));
		
		// second level - third level : not same instance due to isolated status 
		$this->assertEquals(
			$test->getValue('containerIsolated')->getValue('foreignObject')->getId(),
			$test->getValue('containerIsolated')->getValue('containerArrayIsolated')->getValue(0)->getValue(0)->getValue('foreignObject')->getId()
		);
		$this->assertEquals(
			$test->getValue('containerIsolated')->getValue('foreignObject')->getModel()->getName(),
			$test->getValue('containerIsolated')->getValue('containerArrayIsolated')->getValue(0)->getValue(0)->getValue('foreignObject')->getModel()->getName()
		);
		$this->assertNotSame(
			$test->getValue('containerIsolated')->getValue('foreignObject'),
			$test->getValue('containerIsolated')->getValue('containerArrayIsolated')->getValue(0)->getValue(0)->getValue('foreignObject')
		);
		
		$this->assertSame($interfacedObject, $test->export($interfacer));
	}
	
	/**
	 * @dataProvider importInvalidData
	 */
	public function testImportInvalid($interfacedObject, $stackProperties, $exception, $code, $message)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		
		$thrown = false;
		try {
			$model->import($interfacedObject, $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), $stackProperties);
			$this->assertEquals(get_class($e->getOriginalException()), $exception);
			$this->assertEquals($e->getOriginalException()->getCode(), $code);
			$this->assertEquals($e->getOriginalException()->getMessage(), $message);
		}
		$this->assertTrue($thrown);
	}
	
	public function importInvalidData()
	{
		return [
			[
				[
					'id' => 0,
					'foreignObject' => 'name_one',
					'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
					'containerArrayIsolated' => [[[
						'id' => 0,
						'foreignObject' => 'name_one',
					]]]
				],
				'.containerArrayIsolated.0.0',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				"Duplicated id '0'"
			],
			[
				[
					'id' => 0,
					'foreignObject' => 'name_one',
					'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
					'containerIsolated' => [
						'id' => 0,
						'foreignObject' => 'name_one',
					]
				],
				'.containerIsolated',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				"Duplicated id '0'"
			],
			[
				[
					'id' => 0,
					'foreignObject' => 'name_one',
					'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
					'containerIsolated' => [
						'id' => 1,
						'foreignObject' => 'name_one',
						'containerArrayIsolated' => [[
							[
								'id' => 2,
								'foreignObject' => 'name_one',
								'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
							],
						]]
					]
				],
				'.containerIsolated.foreignObject',
				NotReferencedValueException::class,
				ConstantException::NOT_REFERENCED_VALUE_EXCEPTION,
				"foreign value with model 'Test\Basic\Standard' and id 'name_one' not referenced in interfaced object"
			],
			[
				[
					'id' => 0,
					'foreignObject' => 'name_one',
					'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
					'containerIsolated' => [
						'id' => 1,
						'foreignObject' => 'name_one',
						'objects' => [[['name' => 'name_one'], ['name' => 'name_one']]]
					]
				],
				'.containerIsolated.objects.0.1',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				"Duplicated id 'name_one'"
			],
			[
				[
					'id' => 0,
					'foreignObject' => 'name_one',
					'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
					'containerIsolated' => [
						'id' => 1,
						'foreignObject' => 'name_one',
						'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
						'containerArrayIsolated' => [[
							[
								'id' => 2,
								'foreignObject' => 'name_one',
								'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
							],
							[
								'id' => 2,
								'foreignObject' => 'name_one',
								'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
							]
						]]
					]
				],
				'.containerIsolated.containerArrayIsolated.0.1',
				DuplicatedIdException::class,
				ConstantException::DUPLICATED_ID_EXCEPTION,
				"Duplicated id '2'"
				
			]
		];
	}
	
	public function testExportDuplicatedId1()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		$object = $model->getObjectInstance();
		$object->setId(0);
		$container = $object->initValue('containerIsolated');
		$container->setId(0);
		
		$this->expectException(ExportException::class);
		$this->expectExceptionMessage("Something goes wrong on '.containerIsolated' value : \nDuplicated id '0'");
		$object->export($interfacer);
	}
	
	public function testExportDuplicatedId2()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		$object = $model->getObjectInstance();
		$object->setId(0);
		$containerArray = $object->initValue('containerArrayIsolated');
		$array = $containerArray->initValue(0);
		$container = $array->initValue(0);
		$container->setId(0);
		
		$this->expectException(ExportException::class);
		$this->expectExceptionMessage("Something goes wrong on '.containerArrayIsolated.0.0' value : \nDuplicated id '0'");
		$object->export($interfacer);
	}
	
	public function testExportDuplicatedId3()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		$object = $model->getObjectInstance();
		$object->setId(0);
		$containerArray = $object->initValue('containerArrayIsolated');
		$array = $containerArray->initValue(0);
		$container = $array->initValue(0);
		$container->setId(1);
		$containerArray->pushValue($array);
		
		$this->expectException(ExportException::class);
		$this->expectExceptionMessage("Something goes wrong on '.containerArrayIsolated.1.0' value : \nDuplicated id '1'");
		$object->export($interfacer);
	}
	
	public function testExportDuplicatedId4()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacer = new AssocArrayInterfacer();
		$object = $model->getObjectInstance();
		$object->setId(0);
		$containerArray = $object->initValue('containerArrayIsolated');
		$array = $containerArray->initValue(0);
		$container = $array->initValue(0);
		$container->setId(1);
		$objects = $container->initValue('objects');
		$array = $objects->initValue(0);
		$obj = $array->initValue(0);
		$obj->setId('one');
		$objects->pushValue($array);
		
		$this->expectException(ExportException::class);
		$this->expectExceptionMessage("Something goes wrong on '.containerArrayIsolated.0.0.objects.1.0' value : \nDuplicated id 'one'");
		$object->export($interfacer);
	}
	
	
	
	public function testExportNotReferenced()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Isolated');
		$interfacedObject = [
			'id' => 0,
			'foreignObject' => 'name_one',
			'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]],
			'containerIsolated' => [
				'id' => 1,
				'foreignObject' => 'name_one',
				'containerArrayIsolated' => [[
					[
						'id' => 2,
						'foreignObject' => 'name_one',
						'objects' => [[['name' => 'name_one'], ['name' => 'name_two']]]
					],
				]]
			]
		];
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setVerifyReferences(false);
		$object = $model->import($interfacedObject, $interfacer);
		
		$this->expectException(ExportException::class);
		$this->expectExceptionMessage("Something goes wrong on '.containerIsolated.foreignObject' value : \nforeign value with model 'Test\Basic\Standard' and id 'name_one' not referenced in interfaced object");
		$interfacer->setVerifyReferences(true);
		$object->export($interfacer);
	}
}
