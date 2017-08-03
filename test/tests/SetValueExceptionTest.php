<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Object\ComhonObject;
use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Object\Object;

$time_start = microtime(true);

/**
 * test function setValue() with unexpected value type 
 * 
 * @param ComhonObject $object
 * @param string $property
 * @param mixed $value
 * @param string $expectedMessage
 * @throws ComhonException
 */
function testSetUnexpectedValue(ComhonObject $object, $property, $value, $expectedMessage) {
	$throw = true;
	try {
		$object->setValue($property, $value);
	} catch (UnexpectedValueTypeException $e) {
		if ($e->getCode() !== ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION) {
			throw new ComhonException("unexpected exception code {$e->getCode()}");
		}
		if ($e->getMessage() !== $expectedMessage) {
			throw new ComhonException("unexpected exception message \"{$e->getMessage()}\" !== \"$expectedMessage\"");
		}
		$trace = $e->getTrace();
		if ($trace[0]['function'] !== 'setValue') {
			throw new \Exception("unexpected exception trace, unexpected last function : ".json_encode($e->getTrace()));
		}
		$throw = false;
	}
	if ($throw) {
		throw new ComhonException("should throw exception when set property '$property' with value ".$value);
	}
}


$testModel = ModelManager::getInstance()->getInstanceModel('test');
$test = $testModel->getObjectInstance();

$test->setValue('stringValue', null);
$test->setValue('booleanValue', null);
$test->setValue('floatValue', null);
$test->setValue('indexValue', null);
$test->setValue('percentageValue', null);
$test->setValue('dateValue', null);
$test->setValue('objectValue', null);
$test->setValue('objectValues', null);

testSetUnexpectedValue($test, 'stringValue',     true,                      "value must be a string, boolean 'true' given");
testSetUnexpectedValue($test, 'booleanValue',    12,                        "value must be a boolean, integer '12' given");
testSetUnexpectedValue($test, 'floatValue',      'a_string',                "value must be a double, string 'a_string' given");
testSetUnexpectedValue($test, 'indexValue',      new Object('person'),      "value must be a integer, Comhon\Object\Object(person) given");
testSetUnexpectedValue($test, 'indexValue',      -12,                       "value must be a positive integer (including 0), integer '-12' given");
testSetUnexpectedValue($test, 'percentageValue', 'a_string',                "value must be a double, string 'a_string' given");
testSetUnexpectedValue($test, 'dateValue',       new DateTime(),            "value must be a Comhon\Object\ComhonDateTime, DateTime given");
testSetUnexpectedValue($test, 'objectValue',     new Object('person'),      "value must be a Comhon\Object\Object(test\object), Comhon\Object\Object(person) given");
testSetUnexpectedValue($test, 'objectValues',    new Object('test\object'), "value must be a Comhon\Object\ObjectArray(test\object), Comhon\Object\Object(test\object) given");

$time_end = microtime(true);
var_dump('set unexpected value type test exec time '.($time_end - $time_start));
