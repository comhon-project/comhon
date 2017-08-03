<?php

use Comhon\Model\Restriction\Interval;
use Comhon\Model\ModelRestrictedArray;
use Comhon\Object\ObjectArray;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonDateTime;
use Comhon\Exception\NotSatisfiedRestrictionException;
use Comhon\Object\ComhonObject as Object;
use Comhon\Model\Restriction\Regex;
use Comhon\Model\Restriction\Enum;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Interfacer\ExportException;

$time_start = microtime(true);

$testRestrictedModel = ModelManager::getInstance()->getInstanceModel('testRestricted');
$testRestricted = $testRestrictedModel->getObjectInstance();

/** ******************* test setValue fail ********************** **/

function checkTraceLastFunction(\Exception $e) {
	$trace = $e->getTrace();
	if (!in_array($trace[0]['function'], ['setValue', 'pushValue', 'unshiftValue'])) {
		throw new \Exception("unexpected exception trace, unexpected last function : ".json_encode($e->getTrace()));
	}
}

function testSetBadValue(Object $object, $propertyName, $value) {
	try {
		$object->setValue($propertyName, $value);
		$throw = true;
	} catch (NotSatisfiedRestrictionException $e) {
		checkTraceLastFunction($e);
		$throw = false;
	}
	if ($throw) {
		throw new \Exception("set value with bad value should't work ($propertyName)");
	}
}

function testSetBadArrayValue(Object $object, $propertyName, $value) {
	try {
		$object->setValue($propertyName, $value);
		$throw = true;
	} catch (ComhonException $e) {
		checkTraceLastFunction($e);
		$throw = false;
	}
	if ($throw) {
		throw new \Exception("set value with bad value should't work ($propertyName)");
	}
}

function testPushBadValue(Object $object, $value) {
	try {
		$object->pushValue($value);
		$throw = true;
	} catch (NotSatisfiedRestrictionException $e) {
		checkTraceLastFunction($e);
		$throw = false;
	}
	if ($throw) {
		throw new \Exception('set value with bad value should\'t work');
	}
}

function testUnshiftBadValue(Object $object, $value) {
	try {
		$object->unshiftValue($value);
		$throw = true;
	} catch (NotSatisfiedRestrictionException $e) {
		checkTraceLastFunction($e);
		$throw = false;
	}
	if ($throw) {
		throw new \Exception('set value with bad value should\'t work');
	}
}

testSetBadValue($testRestricted, 'enumValue', 'a_string');
testSetBadValue($testRestricted, 'enumValue', true);
testSetBadValue($testRestricted, 'enumValue', null);

$objectArray = $testRestricted->initValue('enumIntArray');
testPushBadValue($objectArray, 10);
testUnshiftBadValue($objectArray, 12);
testSetBadValue($objectArray, 0, 8);
testSetBadValue($objectArray, 0, null);

$objectArray = $testRestricted->initValue('enumFloatArray');
testSetBadValue($objectArray, 0, 1.6);
testUnshiftBadValue($objectArray, 3.55);
testPushBadValue($objectArray, 3.55);
testPushBadValue($objectArray, null);

testSetBadValue($testRestricted, 'color', 'rgb(aze,)');
testSetBadValue($testRestricted, 'color', null);

$objectArray = $testRestricted->initValue('emails');
testPushBadValue($objectArray, 'azeaze1');
testUnshiftBadValue($objectArray, 'azeaze2');
testSetBadValue($objectArray, 0, 'azeaze3');
testSetBadValue($objectArray, 0, null);

testSetBadValue($testRestricted, 'naturalNumber', -4);
testSetBadValue($testRestricted, 'naturalNumber', null);

testSetBadValue($testRestricted, 'birthDate', new ComhonDateTime('now'));
testSetBadValue($testRestricted, 'birthDate', null);

$objectArray = $testRestricted->initValue('intervalInArray');
testSetBadValue($objectArray, 0, 11.6);
testUnshiftBadValue($objectArray, -3.55);
testPushBadValue($objectArray, -3.55);
testPushBadValue($objectArray, null);

/** ********************** test set success *********************** **/

$testRestricted->setValue('color', '#12abA8');

$regexInArray = $testRestricted->getProperty('emails')->getModel()->getObjectInstance();
$regexInArray->pushValue('plop.plop@plop.plop');
$regexInArray->unshiftValue('plop@plop.fr');
$testRestricted->setValue('emails', $regexInArray);

$testRestricted->setValue('naturalNumber', 45);
$testRestricted->setValue('birthDate', new ComhonDateTime('2000-01-01'));

$intervalInArray = $testRestricted->initValue('intervalInArray');
$intervalInArray->pushValue(1);
$intervalInArray->unshiftValue(-1.4);
$testRestricted->setValue('intervalInArray', $intervalInArray);

$testRestricted->initValue('enumIntArray');
$testRestricted->initValue('enumFloatArray');

$testRestricted->setValue('enumValue', 'plop1');
$testRestricted->getValue('enumIntArray')->setValue(0, 1);
$testRestricted->getValue('enumIntArray')->setValue(1, 3);
$testRestricted->getValue('enumFloatArray')->pushValue(1.5);
$testRestricted->getValue('enumFloatArray')->pushValue(3.5);


/** ************** test export import with values not in restriction fail *************** **/

$testRestricted->getValue('enumFloatArray')->pushValue(4.5, true, false);

try {
	$testRestricted->export($stdPrivateInterfacer);
	$throw = true;
} catch (ExportException $e) {
	if (!($e->getOriginalException() instanceof NotSatisfiedRestrictionException)) {
		throw new \Exception('wrong exception');
	}
	$throw = false;
}
if ($throw) {
	throw new \Exception('export with values not in enum');
}

$testRestricted->getValue('enumFloatArray')->popValue();
$testRestricted->export($stdPrivateInterfacer);
$testRestricted->getValue('enumFloatArray')->unshiftValue(4.5, true, false);

try {
	$testRestricted->export($stdPrivateInterfacer);
	$throw = true;
} catch (ExportException $e) {
	if (!($e->getOriginalException() instanceof NotSatisfiedRestrictionException)) {
		throw new \Exception('wrong exception');
	}
	$throw = false;
}
if ($throw) {
	throw new \Exception('export with values not in enum');
}
$testRestricted->getValue('enumFloatArray')->shiftValue();
$testRestricted->export($stdPrivateInterfacer);

if (!compareJson(json_encode($testRestricted->export($stdPrivateInterfacer)), '{"enumIntArray":[1,3],"enumFloatArray":[1.5,3.5],"emails":["plop@plop.fr","plop.plop@plop.plop"],"intervalInArray":[-1.4,1],"color":"#12abA8","naturalNumber":45,"birthDate":"2000-01-01T00:00:00+01:00","enumValue":"plop1"}')) {
	throw new \Exception('bad value');
}
$testRestricted->fill(json_decode('{"color":"#12abA8","emails":[],"naturalNumber":45,"birthDate":"2000-01-01T00:00:00+01:00","intervalInArray":[],"enumIntArray":[],"enumFloatArray":[]}'), $stdPrivateInterfacer);

try {
	$testRestricted->fill(json_decode('{"color":"#12abA8","emails":[],"naturalNumber":-5,"birthDate":"2000-01-01T00:00:00+01:00","intervalInArray":[],"enumIntArray":[],"enumFloatArray":[]}'), $stdPrivateInterfacer);
	$throw = true;
} catch (ImportException $e) {
	if (!($e->getOriginalException() instanceof NotSatisfiedRestrictionException)) {
		throw new ComhonException("wrong exception");
	}
	$throw = false;
}
if ($throw) {
	throw new \Exception('export with values not in enum');
}

/** ************** test set value array with good restriction but not same instance *************** **/

$restriction = new Interval(']-1.50, 2[', $modelFloat);
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'intervalValue');
$objectArray = new ObjectArray($modelRestrictedArray);
$testRestricted->setValue('intervalInArray', $objectArray);

$restriction = new Regex('email');
$modelRestrictedArray = new ModelRestrictedArray($modelString, $restriction, 'email');
$objectArray = new ObjectArray($modelRestrictedArray);
$testRestricted->setValue('emails', $objectArray);

$restriction = new Enum([3.5, 1.5]);
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'enumArrayValue');
$objectArray = new ObjectArray($modelRestrictedArray);
$testRestricted->setValue('enumFloatArray', $objectArray);

/** ************** test set value array with bad restriction *************** **/

$modelFloat  = ModelManager::getInstance()->getInstanceModel('float');
$modelString = ModelManager::getInstance()->getInstanceModel('string');

// Interval should be ']-1.50, 2['
$restriction = new Interval(']-1.50, 2]', $modelFloat);
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'intervalValue');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'intervalInArray', $objectArray);

$restriction = new Regex('color');
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'intervalValue');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'intervalInArray', $objectArray);

// Regex should be 'email'
$restriction = new Regex('color');
$modelRestrictedArray = new ModelRestrictedArray($modelString, $restriction, 'email');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'emails', $objectArray);

$restriction = new Interval(']-1.50, 2]', $modelFloat);
$modelRestrictedArray = new ModelRestrictedArray($modelString, $restriction, 'email');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'emails', $objectArray);

// Enumshould be [3.5, 1.5]
$restriction = new Enum([30.5, 1.5]);
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'enumArrayValue');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'enumFloatArray', $objectArray);

$restriction = new Interval(']-1.50, 2]', $modelFloat);
$modelRestrictedArray = new ModelRestrictedArray($modelFloat, $restriction, 'enumArrayValue');
$objectArray = new ObjectArray($modelRestrictedArray);
testSetBadArrayValue($testRestricted, 'enumFloatArray', $objectArray);


$time_end = microtime(true);
var_dump('value restriction test exec time '.($time_end - $time_start));
