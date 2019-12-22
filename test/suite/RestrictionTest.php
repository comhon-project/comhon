<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Property\Property;
use Comhon\Model\Restriction\Enum;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Model\Restriction\Restriction;
use Comhon\Model\Restriction\NotNull;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Object\ComhonArray;
use Comhon\Model\Restriction\Size;
use Comhon\Model\Restriction\Interval;
use Comhon\Model\ModelArray;
use Comhon\Model\Restriction\NotEmptyString;
use Comhon\Model\Restriction\NotEmptyArray;
use Comhon\Model\Restriction\Length;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Object\MissingRequiredValueException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Model\Restriction\ModelName;

class RestrictionTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testFirstNotSatisifedRestriction()
	{
		$modelInt = ModelManager::getInstance()->getInstanceModel('integer');
		$restrictionOne = new Enum([1, 2]);
		$restrictionTwo = new Enum([1, 3]);
		
		$this->assertSame($restrictionTwo, Restriction::getFirstNotSatisifed([$restrictionOne, $restrictionTwo], 2));
		
		$property = new Property($modelInt, 'hehe', null, false, false, false, true, false, null, null, [$restrictionOne, $restrictionTwo]);
		$this->assertSame($restrictionTwo, Restriction::getFirstNotSatisifed($property->getRestrictions(), 2));
	}
	
	/** ************************************** not null ************************************** **/
	
	public function testNotNullRestrictionInstance()
	{
		$notNull = new NotNull();
		$this->assertTrue($notNull->isEqual($notNull));
		$this->assertTrue($notNull->isEqual(new NotNull()));
		$this->assertFalse($notNull->isEqual(new Enum([1, 2])));
		
		$this->assertTrue($notNull->satisfy(1));
		$this->assertFalse($notNull->satisfy(null));
		
		$this->assertEquals('not null value given', $notNull->toMessage(1));
		$this->assertEquals('null value given, value must be not null', $notNull->toMessage(null));
		
		$this->assertEquals('Not null', $notNull->toString());
	}
	
	public function testNotNullRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		
		$this->assertCount(0, $model->getProperty('notNullArray')->getRestrictions());
		$this->assertTrue($model->getProperty('notNullArray')->isNotNull());
		
		/** @var \Comhon\Model\ModelArray $modelArray */
		$modelArray = $model->getProperty('notNullArray')->getModel();
		$this->assertInstanceOf(ModelArray::class, $modelArray);
		$this->assertCount(1, $modelArray->getElementRestrictions());
		$this->assertTrue($modelArray->isNotNullElement());
	}
	
	public function testNotNullRestrictionValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->initValue('notNullArray');
		$obj->setValue('notNullArray', $array);
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('notNullArray', null);
	}
	
	public function testNotNullRestrictionArrayValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->initValue('notNullArray');
		$array->pushValue(1.5);
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array->pushValue(null);
	}
	
	public function testNotNullRestrictionAggregation()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$obj = $model->getObjectInstance();
		$obj->initValue('homes');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('homes', null);
	}
	
	public function testNotNullRestrictionForeign()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		$obj->initValue('notNullForeign');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('notNullForeign', null);
	}
	
	/** ************************************** not empty ************************************** **/
	
	public function testNotEmptyStringRestrictionInstance()
	{
		$notEmpty = new NotEmptyString();
		$this->assertTrue($notEmpty->isEqual($notEmpty));
		$this->assertTrue($notEmpty->isEqual(new NotEmptyString()));
		$this->assertFalse($notEmpty->isEqual(new Enum([1, 2])));
		
		$this->assertTrue($notEmpty->satisfy(1));
		$this->assertTrue($notEmpty->satisfy('1'));
		$this->assertFalse($notEmpty->satisfy(null));
		$this->assertFalse($notEmpty->satisfy(''));
		$this->assertFalse($notEmpty->satisfy('0'));
		$this->assertFalse($notEmpty->satisfy(0));
		$this->assertFalse($notEmpty->satisfy(false));
		
		$this->assertEquals('value is not empty', $notEmpty->toMessage(1));
		$this->assertEquals('value is empty, value must be not empty', $notEmpty->toMessage(null));
		
		$this->assertEquals('Not empty', $notEmpty->toString());
	}
	
	public function testNotEmptyArrayRestrictionInstance()
	{
		$notEmpty = new NotEmptyArray();
		$this->assertTrue($notEmpty->isEqual($notEmpty));
		$this->assertTrue($notEmpty->isEqual(new NotEmptyArray()));
		$this->assertFalse($notEmpty->isEqual(new Enum([1, 2])));
		
		$array = new ComhonArray('string', false, 'element');
		$this->assertFalse($notEmpty->satisfy($array));
		$this->assertFalse($notEmpty->satisfy($array, 0));
		$this->assertTrue($notEmpty->satisfy($array, 1));
		$this->assertFalse($notEmpty->satisfy($array, -1));
		$array->pushValue('1');
		$this->assertTrue($notEmpty->satisfy($array));
		$this->assertTrue($notEmpty->satisfy($array, 0));
		$this->assertTrue($notEmpty->satisfy($array, 1));
		$this->assertFalse($notEmpty->satisfy($array, -1));
		
		$this->assertEquals('value is not empty', $notEmpty->toMessage(1));
		$this->assertEquals('value is empty, value must be not empty', $notEmpty->toMessage(null));
		$this->assertEquals('value is not empty', $notEmpty->toMessage($array));
		$this->assertEquals('value is not empty', $notEmpty->toMessage($array, 0));
		$this->assertEquals('value is not empty', $notEmpty->toMessage($array, 1));
		$this->assertEquals('trying to modify comhon array and make it empty, value must be not empty', $notEmpty->toMessage($array, -1));
		$array->popValue();
		$this->assertEquals('value is empty, value must be not empty', $notEmpty->toMessage($array));
		
		$this->assertEquals('Not empty', $notEmpty->toString());
	}
	
	public function testNotEmptyRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		
		$restrictions = $model->getProperty('notEmpty')->getRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[NotEmptyString::class]));
		$this->assertInstanceOf(NotEmptyString::class, $restrictions[NotEmptyString::class]);
		$this->assertTrue($restrictions[NotEmptyString::class]->satisfy('1'));
		$this->assertFalse($restrictions[NotEmptyString::class]->satisfy(''));
		$this->assertFalse($restrictions[NotEmptyString::class]->satisfy(null));
		
		/** @var \Comhon\Model\ModelArray $modelArray */
		$modelArray = $model->getProperty('notEmptyArray')->getModel();
		$restrictions = $modelArray->getArrayRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[NotEmptyArray::class]));
		$this->assertInstanceOf(NotEmptyArray::class, $restrictions[NotEmptyArray::class]);
		$array = new ComhonArray($model->getProperty('notEmptyArray')->getModel(), false);
		$this->assertFalse($restrictions[NotEmptyArray::class]->satisfy($array));
		$array->pushValue('1');
		$this->assertTrue($restrictions[NotEmptyArray::class]->satisfy($array));
		
		$this->assertInstanceOf(ModelArray::class, $model->getProperty('notEmptyArray')->getModel());
		$restrictions = $modelArray->getElementRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[NotEmptyString::class]));
		$this->assertInstanceOf(NotEmptyString::class, $restrictions[NotEmptyString::class]);
		$this->assertTrue($restrictions[NotEmptyString::class]->satisfy('1'));
		$this->assertFalse($restrictions[NotEmptyString::class]->satisfy(''));
		$this->assertFalse($restrictions[NotEmptyString::class]->satisfy(null));
	}
	
	public function testNotEmptyRestrictionValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$obj->setValue('notEmpty', 'hehe');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('notEmpty', '');
	}
	
	public function testNotEmptyRestrictionArray()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('notEmptyArray', false);
		$array->pushValue('hehe');
		$obj->setValue('notEmptyArray', $array);
		$array->setIsLoaded(true);
		
		$array = $obj->initValue('notEmptyArray', false);
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array->setIsLoaded(true);
	}
	
	public function testSetNotEmptyRestrictionArray()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('notEmptyArray', false);
		$array->pushValue('hehe');
		$obj->setValue('notEmptyArray', $array);
		$array->setIsLoaded(true);
		$array->pushValue('hehe');
		$array->popValue();
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array and make it empty, value must be not empty');
		$array->popValue();
	}
	
	public function testNotEmptyRestrictionArrayValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('notEmptyArray', false);
		$array->pushValue('hehe');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array->pushValue('');
	}
	
	/** ************************************** size ************************************** **/
	
	public function testSizeRestrictionInstance()
	{
		$size = new Size('[3,5]');
		$this->assertTrue($size->isEqual($size));
		$this->assertTrue($size->isEqual(new Size('[3,5]')));
		$this->assertFalse($size->isEqual(new Size('[2,5]')));
		$this->assertFalse($size->isEqual(new Interval('[3,5]', ModelManager::getInstance()->getInstanceModel('integer'))));
		
		$array = new ComhonArray(ModelManager::getInstance()->getInstanceModel('string'));
		$array->pushValue('1');
		$array->pushValue('2');
		$this->assertFalse($size->satisfy($array));
		$array->pushValue('3');
		$this->assertTrue($size->satisfy($array));
		$array->pushValue('4');
		$this->assertTrue($size->satisfy($array));
		$array->pushValue('5');
		$this->assertTrue($size->satisfy($array));
		$array->pushValue('6');
		$this->assertFalse($size->satisfy($array));
		$this->assertFalse($size->satisfy(4));
		
		$this->assertEquals('Value passed to Size must be an ComhonArray, instance of integer given', $size->toMessage(1));
		$this->assertEquals('size 6 of given array is not in size range [3,5]', $size->toMessage($array));
		$this->assertEquals('trying to modify comhon array from size 6 to 7. size 7 of given array is not in size range [3,5]', $size->toMessage($array, 1));
		
		$this->assertEquals('[3,5]', $size->toString());
	}
	
	public function testSizeRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		
		/** @var \Comhon\Model\ModelArray $modelArray */
		$modelArray = $model->getProperty('sizeArray')->getModel();
		$restrictions = $modelArray->getArrayRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[Size::class]));
		$this->assertInstanceOf(Size::class, $restrictions[Size::class]);
		
		$array = new ComhonArray($model->getProperty('sizeArray')->getModel(), false);
		$array->pushValue('111');
		$array->pushValue('222');
		$this->assertFalse($restrictions[Size::class]->satisfy($array));
		$array->pushValue('333');
		$this->assertTrue($restrictions[Size::class]->satisfy($array));
	}
	
	
	public function testSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->pushValue('111');
		$array->pushValue('222');
		$array->pushValue('333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array = $obj->getInstanceValue('sizeArray');
	}
	
	public function testSetSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->setValue(0, '111');
		$array->setValue(1, '222');
		$array->setValue(2, '333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->setValue(3, '444');
		$array->setValue(4, '555');
		$array->setValue(4, '5555'); // must not throw excpetion 
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 5 to 6. size 6 of given array is not in size range [3,5]');
		$array->setValue(5, '666');
	}
	
	public function testUnsetSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->setValue(0, '111');
		$array->setValue(1, '222');
		$array->setValue(2, '333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->setValue(3, '444');
		$array->unsetValue(3);
		$array->unsetValue(3); // must not throw excpetion 
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 3 to 2. size 2 of given array is not in size range [3,5]');
		$array->unsetValue(0);
	}
	
	public function testPushSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->pushValue('111');
		$array->pushValue('222');
		$array->pushValue('333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->pushValue('444');
		$array->pushValue('555');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 5 to 6. size 6 of given array is not in size range [3,5]');
		$array->pushValue('666');
	}
	
	public function testPopSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->pushValue('111');
		$array->pushValue('222');
		$array->pushValue('333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->pushValue('444');
		$array->popValue();
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 3 to 2. size 2 of given array is not in size range [3,5]');
		$array->popValue();
	}
	
	public function testUnshiftSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->unshiftValue('111');
		$array->unshiftValue('222');
		$array->unshiftValue('333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->unshiftValue('444');
		$array->unshiftValue('555');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 5 to 6. size 6 of given array is not in size range [3,5]');
		$array->unshiftValue('666');
	}
	
	public function testShiftSizeEmptyRestriction()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->unshiftValue('111');
		$array->unshiftValue('222');
		$array->unshiftValue('333');
		$obj->setValue('sizeArray', $array);
		$array->setIsLoaded(true);
		$array->unshiftValue('444');
		$array->shiftValue();
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage('trying to modify comhon array from size 3 to 2. size 2 of given array is not in size range [3,5]');
		$array->shiftValue();
	}
	
	/** ************************************** length ************************************** **/
	
	public function testLengthRestrictionInstance()
	{
		$length = new Length('[3,5]');
		$this->assertTrue($length->isEqual($length));
		$this->assertTrue($length->isEqual(new Length('[3,5]')));
		$this->assertFalse($length->isEqual(new Length('[3,4]')));
		$this->assertFalse($length->isEqual(new Enum([1, 2])));
		
		$this->assertTrue($length->satisfy('aaa'));
		$this->assertFalse($length->satisfy('a'));
		$this->assertFalse($length->satisfy('aaaaaa'));
		
		$this->assertEquals('length 3 of given string is in length range [3,5]', $length->toMessage('aaa'));
		$this->assertEquals('length 6 of given string is not in length range [3,5]', $length->toMessage('666666'));
		
		$this->assertEquals('[3,5]', $length->toString());
	}
	
	public function testLengthRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		
		$restrictions = $model->getProperty('length')->getRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[Length::class]));
		$this->assertInstanceOf(Length::class, $restrictions[Length::class]);
		$this->assertTrue($restrictions[Length::class]->satisfy('aaa'));
		$this->assertFalse($restrictions[Length::class]->satisfy('aaaaaa'));
		
		/** @var \Comhon\Model\ModelArray $modelArray */
		$modelArray = $model->getProperty('sizeArray')->getModel();
		$this->assertInstanceOf(ModelArray::class, $model->getProperty('sizeArray')->getModel());
		$restrictions = $modelArray->getElementRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[Length::class]));
		$this->assertInstanceOf(Length::class, $restrictions[Length::class]);
		$this->assertTrue($restrictions[Length::class]->satisfy('aaa'));
		$this->assertFalse($restrictions[Length::class]->satisfy('aaaaaa'));
	}
	
	public function testLengthRestrictionValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$obj->setValue('length', 'aaa');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$obj->setValue('length', 'a');
	}
	
	public function testLengthRestrictionArrayValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance();
		
		$array = $obj->getInstanceValue('sizeArray', false);
		$array->pushValue('aaa');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$array->pushValue('aa');
	}
	
	/** ************************************** model name ************************************** **/
	
	public function testModelNameRestrictionInstance()
	{
		$modelNameRestriction = new ModelName();
		$this->assertTrue($modelNameRestriction->isEqual($modelNameRestriction));
		$this->assertTrue($modelNameRestriction->isEqual(new ModelName()));
		$this->assertFalse($modelNameRestriction->isEqual(new Length('[3,4]')));
		
		$this->assertTrue($modelNameRestriction->satisfy('Test\TestRestricted'));
		$this->assertFalse($modelNameRestriction->satisfy('my_model'));
		
		$this->assertEquals("model 'Test\TestRestricted' exists", $modelNameRestriction->toMessage('Test\TestRestricted'));
		$this->assertEquals("model 'my_model' doesn't exist", $modelNameRestriction->toMessage('my_model'));
		
		$this->assertEquals('Model name', $modelNameRestriction->toString());
	}
	
	public function testModelNameRestrictionProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Simple');
		
		$restrictions = $model->getProperty('model')->getRestrictions();
		$this->assertCount(1, $restrictions);
		$this->assertTrue(isset($restrictions[ModelName::class]));
		$this->assertInstanceOf(ModelName::class, $restrictions[ModelName::class]);
		$this->assertTrue($restrictions[ModelName::class]->satisfy('Test\TestRestricted'));
		$this->assertFalse($restrictions[ModelName::class]->satisfy('my_model'));
	}
	
	public function testModelNameRestrictionValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Simple');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('model', 'Comhon\SqlTable');
		
		$this->expectException(NotSatisfiedRestrictionException::class);
		$this->expectExceptionMessage("model 'my_model' doesn't exist");
		$obj->setValue('model', 'my_model');
	}
	
	/** ************************************** import/export successful ************************************** **/
	
	public function testImportExportSuccessful()
	{
		$interfacer = new AssocArrayInterfacer();
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$assocArray = [
			'color' => '#000000',
			'notNullArray' => [
				1.5
			],
			'sizeArray' => [
				'aaa',
				'aaa',
				'aaa'
			]
		];
		$obj = $model->import($assocArray, $interfacer);
		$this->assertTrue($obj->isLoaded());
		$this->assertEquals('{"color":"#000000","notNullArray":[1.5],"sizeArray":["aaa","aaa","aaa"]}', $interfacer->toString($obj->export($interfacer)));
	}
	
	/** ************************************** import failure ************************************** **/
	
	/**
	 * @dataProvider importData
	 */
	public function testImportFailure($assocArray, $propertyString, $exceptionClass, $code, $message) {
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$interfacer = new AssocArrayInterfacer();
		
		$thrown = false;
		try {
			$model->import($assocArray, $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), $propertyString);
			$this->assertEquals(get_class($e->getOriginalException()), $exceptionClass);
			$this->assertEquals($e->getOriginalException()->getCode(), $code);
			$this->assertEquals($e->getOriginalException()->getMessage(), $message);
		}
		$this->assertTrue($thrown);
	}
	
	public function importData() {
		return [
			[
				['naturalNumber' => -1],
				'.naturalNumber',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				'-1 is not in interval [0,]'
			],
			[
				['notNullArray' => null],
				'.notNullArray',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				'null value given, value must be not null'
			],
			[
				['notNullArray' => [null]],
				'.notNullArray.0',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				'null value given, value must be not null'
			],
			[
				['sizeArray' => ['aaa']],
				'.sizeArray',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				'size 1 of given array is not in size range [3,5]'
			],
			[
				['sizeArray' => ['aaa', 'aaa', 'aa']],
				'.sizeArray.2',
				NotSatisfiedRestrictionException::class,
				ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION,
				'length 2 of given string is not in length range [3,4]'
			]
		];
	}
	
	public function testImportArrayFailure() {
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$model = $model->getProperty('sizeArray')->getModel();
		$interfacer = new AssocArrayInterfacer();
		
		$thrown = false;
		try {
			$model->import(["aaa"], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "size 1 of given array is not in size range [3,5]");
		}
		$this->assertTrue($thrown);
	}
	
	public function testFillArrayFailure() {
		
		$modelOne = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$model = $modelOne->getProperty('sizeArray')->getModel();
		$interfacer = new AssocArrayInterfacer();
		
		$thrown = false;
		try {
			$model->getObjectInstance(false)->fill(["aaa"], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "size 1 of given array is not in size range [3,5]");
		}
		$this->assertTrue($thrown);
	}
	
	/** ************************************** export failure ************************************** **/
	
	public function testExportFailure() {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\TestRestricted');
		$obj = $model->getObjectInstance(false);
		$obj2 = $obj->initValue('sizeArray', false);
		
		$thrown = false;
		try {
			$obj->export($interfacer);
		} catch (ExportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.sizeArray');
			$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "size 0 of given array is not in size range [3,5]");
		}
		$this->assertTrue($thrown);
		
		
		$thrown = false;
		try {
			$obj2->export($interfacer);
		} catch (ExportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), NotSatisfiedRestrictionException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "size 0 of given array is not in size range [3,5]");
		}
		$this->assertTrue($thrown);
	}
	
}
