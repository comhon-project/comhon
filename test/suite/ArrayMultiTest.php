<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelInteger;
use Comhon\Model\Restriction\Interval;
use Comhon\Model\Restriction\Size;
use Comhon\Model\ModelForeign;
use Comhon\Model\Model;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;

class ArrayMultiTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testInstanciateModelWithArrayMulti()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti');
		
		$this->assertTrue($model->hasProperty('objects'));
		$this->assertInstanceOf(ForeignProperty::class, $model->getProperty('objects'));
		$foreignModel = $model->getProperty('objects')->getModel();
		$this->assertInstanceOf(ModelForeign::class, $foreignModel);
		$arrayOneModel = $foreignModel->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayOneModel);
		$this->assertTrue($arrayOneModel->isAssociative());
		$arrayTwoModel = $arrayOneModel->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayTwoModel);
		$this->assertFalse($arrayTwoModel->isAssociative());
		$arrayThreeModel = $arrayTwoModel->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayThreeModel);
		$this->assertTrue($arrayThreeModel->isAssociative());
		$this->assertInstanceOf(Model::class, $arrayThreeModel->getModel());
		$this->assertEquals('Test\Basic\Standard', $arrayThreeModel->getModel()->getName());
		
		$this->assertTrue($model->hasProperty('integers'));
		$arrayOneModel = $model->getProperty('integers')->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayOneModel);
		$arrayTwoModel = $arrayOneModel->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayTwoModel);
		$arrayThreeModel = $arrayTwoModel->getModel();
		$this->assertInstanceOf(ModelArray::class, $arrayThreeModel);
		$this->assertInstanceOf(ModelInteger::class, $arrayThreeModel->getModel());
		$this->assertEquals('integer', $arrayThreeModel->getModel()->getName());
		
		$this->assertTrue($arrayOneModel->isNotNullElement());
		$this->assertCount(0, $arrayOneModel->getArrayRestrictions());
		
		$this->assertTrue($arrayTwoModel->isNotNullElement());
		$arrayRestrictions = $arrayTwoModel->getArrayRestrictions();
		$this->assertCount(1, $arrayRestrictions);
		$this->assertArrayHasKey(Size::class, $arrayRestrictions);
		$this->assertEquals('[,1]', $arrayRestrictions[Size::class]->toString());
		
		$this->assertTrue($arrayThreeModel->isNotNullElement());
		$arrayRestrictions = $arrayThreeModel->getArrayRestrictions();
		$this->assertCount(1, $arrayRestrictions);
		$this->assertArrayHasKey(Size::class, $arrayRestrictions);
		$this->assertEquals('[,2]', $arrayRestrictions[Size::class]->toString());
		$arrayRestrictions = $arrayThreeModel->getElementRestrictions();
		$this->assertCount(1, $arrayRestrictions);
		$this->assertArrayHasKey(Interval::class, $arrayRestrictions);
		$this->assertEquals('[,100]', $arrayRestrictions[Interval::class]->toString());
	}
	
	public function testInstanciateObjectWithArrayMulti()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti');
		$object = $model->getObjectInstance();
		$arrayOne = $object->initValue('integers');
		$arrayTwo1 = $arrayOne->initValue(0);
		$arrayThree1 = $arrayTwo1->initValue(0);
		$arrayThree1->pushValue(10);
		$arrayThree1->pushValue(20);
		$arrayTwo2 = $arrayOne->initValue(1);
		$arrayThree2 = $arrayTwo2->initValue(0);
		$arrayThree2->pushValue(30);
		$arrayThree2->pushValue(40);
		$this->assertEquals(30, $object->getValue('integers')->getValue(1)->getValue(0)->getValue(0));
		
		$arrayOne = $object->initValue('objects');
		$arrayTwo1 = $arrayOne->initValue('key_zero');
		$arrayThree1 = $arrayTwo1->initValue(0);
		$obj = $arrayThree1->initValue('key_one');
		$obj->setId('one');
		$obj = $arrayThree1->initValue('key_two');
		$obj->setId('two');
		$arrayThree2 = $arrayTwo1->initValue(1);
		$obj = $arrayThree2->initValue('key_three');
		$obj->setId('three');
		$obj = $arrayThree2->initValue('key_four');
		$obj->setId('four');
		$this->assertEquals('four', $object->getValue('objects')->getValue('key_zero')->getValue(1)->getValue('key_four')->getId());
	}
	
	public function testImportExportValid()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti');
		$json = '{
		    "integers": [
		        [
		            [10,20]
		        ],
		        [
		            [30,40]
		        ]
		    ],
		    "objects": {
		        "key_zero": [
		            {"key_one": "one","key_two": "two"},
		            {"key_three": "three","key_four": "four"}
		        ]
		    }
		}';
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setVerifyReferences(false);
		$interfacedObject = $interfacer->fromString($json);
		$object = $model->import($interfacedObject, $interfacer);
		
		$interfacer = new XMLInterfacer();
		$interfacer->setVerifyReferences(false);
		$xmlExported = $interfacer->toString($object->export($interfacer), true);
		$xmlOrigin = '<root>
  <integers>
    <x>
      <y>
        <z>10</z>
        <z>20</z>
      </y>
    </x>
    <x>
      <y>
        <z>30</z>
        <z>40</z>
      </y>
    </x>
  </integers>
  <objects>
    <first key-="key_zero">
      <second>
        <third key-="key_one">one</third>
        <third key-="key_two">two</third>
      </second>
      <second>
        <third key-="key_three">three</third>
        <third key-="key_four">four</third>
      </second>
    </first>
  </objects>
</root>';
		
		$this->assertEquals($xmlOrigin, $xmlExported);
	}
	
	/**
	 * @dataProvider importData
	 */
	public function testImportInvalid($json, $stackProperties, $exception, $code, $message)
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti');
		$interfacer = new AssocArrayInterfacer();
		
		$thrown = false;
		try {
			$model->import(json_decode($json, true), $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), $stackProperties);
			$this->assertEquals(get_class($e->getOriginalException()), $exception);
			$this->assertEquals($e->getOriginalException()->getCode(), $code);
			$this->assertEquals($e->getOriginalException()->getMessage(), $message);
		}
		$this->assertTrue($thrown);
	}
	
	public function importData()
	{
		return [
			[
				'{"integers": [[[30,"aaa"]]]}',
				'.integers.0.0.1',
				UnexpectedValueTypeException::class,
				ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
				"value must be a integer, string 'aaa' given"
			],
			[
				'{"integers": [[[300]]]}',
				'.integers.0.0.0',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				"300 is not in interval [,100]"
			],
			[
				'{"integers": [[[30,null]]]}',
				'.integers.0.0.1',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				"null value given, value must be not null"
			],
			[
				'{"integers": [[30,40]]}',
				'.integers.0.0',
				UnexpectedValueTypeException::class,
				ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION,
				"value must be a array, integer '30' given"
			],
			[
				'{"integers": [[null]]}',
				'.integers.0.0',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				"null value given, value must be not null"
			],
			[
				'{"integers": [[[],[]]]}',
				'.integers.0',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				"size 2 of given array is not in size range [,1]"
			],
		];
	}
	
	public function testAffectValidArray()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Basic\Standard');
		$modelArray = new ModelArray(new ModelArray(new ModelArray($model, true, 'third'), false, 'second'), true, 'first');
		$object = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti')->getObjectInstance();
		$array = $modelArray->getObjectInstance();
		
		// must works
		$object->setValue('objects', $array);
		$this->assertTrue(true);
	}
	
	/**
	 * @dataProvider invalidModelArrayData
	 */
	public function testAffectInValidArray($arrayInfos, $modelname, $exception, $message)
	{
		list($isAssociative, $elementName, $notNull) = array_pop($arrayInfos);
		$model = ModelManager::getInstance()->getInstanceModel($modelname);
		$modelArray = new ModelArray($model, $isAssociative, $elementName, [], [], $notNull);
		while ($current = array_pop($arrayInfos)) {
			list($isAssociative, $elementName, $notNull) = $current;
			$modelArray = new ModelArray($modelArray, $isAssociative, $elementName, [], [], $notNull);
		}
		$object = ModelManager::getInstance()->getInstanceModel('Test\ArrayMulti')->getObjectInstance();
		$array = $modelArray->getObjectInstance();
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		$object->setValue('objects', $array);
	}
	
	public function invalidModelArrayData()
	{
		return [
			[
				[[true, 'first', false], [false, 'second', false], [false, 'third', false]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"ModelArray must be associative. array depth : 2"
			],
			[
				[[true, 'first', false], [true, 'second', false], [true, 'third', false]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"ModelArray must not be associative. array depth : 1"
			],
			[
				[[true, 'first', false], [false, 'third', false]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"ModelArray element name must be 'second', 'third' given. array depth : 1"
			],
			[
				[[true, 'first', false], [false, 'second', false]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"model must be a Comhon\Model\ModelArray, model 'Test\Basic\Standard' given. array depth : 2"
			],
			[
				[[true, 'first', false], [false, 'second', false], [true, 'third', false]],
				'Test\ArrayMulti',
				UnexpectedValueTypeException::class,
				"model must be a 'Test\Basic\Standard', model 'Test\ArrayMulti' given. array depth : 2"
			],
			[
				[[true, 'first', false], [false, 'second', false], [true, 'third', false]],
				'string',
				UnexpectedValueTypeException::class,
				"model must be a 'Test\Basic\Standard', model 'string' given. array depth : 2"
			],
			[
				[[true, 'first', false], [false, 'second', false], [true, 'third', false], [true, 'fourth', false]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"model must be a 'Test\Basic\Standard', model Comhon\Model\ModelArray given. array depth : 2"
			],
			[
				[[true, 'first', false], [false, 'second', false], [true, 'third', true]],
				'Test\Basic\Standard',
				UnexpectedValueTypeException::class,
				"ModelArray must not have not null element. array depth : 2"
			],
		];
	}
}
