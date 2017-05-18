<?php

use comhon\model\restriction\Interval;
use comhon\model\ModelRestrictedArray;
use comhon\object\ObjectArray;
use comhon\model\singleton\ModelManager;
use comhon\object\ComhonDateTime;
use comhon\exception\NotSatisfiedRestrictionException;
use comhon\object\Object;

$time_start = microtime(true);

$lTestRestrictedModel = ModelManager::getInstance()->getInstanceModel('testRestricted');
$lTestRestricted = $lTestRestrictedModel->getObjectInstance();

/** ******************* test setValue fail ********************** **/

function testSetBadValue(Object $pObject, $pPropertyName, $pValue) {
	try {
		$pObject->setValue($pPropertyName, $pValue);
		$lThrow = true;
	} catch (NotSatisfiedRestrictionException $e) {
		$lThrow = false;
	}
	if ($lThrow) {
		throw new \Exception("set value with bad value should't work ($pPropertyName)");
	}
}

function testPushBadValue(Object $pObject, $pValue) {
	try {
		$pObject->pushValue($pValue);
		$lThrow = true;
	} catch (NotSatisfiedRestrictionException $e) {
		$lThrow = false;
	}
	if ($lThrow) {
		throw new \Exception('set value with bad value should\'t work');
	}
}

function testUnshiftBadValue(Object $pObject, $pValue) {
	try {
		$pObject->unshiftValue($pValue);
		$lThrow = true;
	} catch (NotSatisfiedRestrictionException $e) {
		$lThrow = false;
	}
	if ($lThrow) {
		throw new \Exception('set value with bad value should\'t work');
	}
}

testSetBadValue($lTestRestricted, 'enumValue', 'a_string');
testSetBadValue($lTestRestricted, 'enumValue', true);
testSetBadValue($lTestRestricted, 'enumValue', null);

$lObjectArray = $lTestRestricted->initValue('enumIntArray');
testPushBadValue($lObjectArray, 10);
testUnshiftBadValue($lObjectArray, 12);
testSetBadValue($lObjectArray, 0, 8);
testSetBadValue($lObjectArray, 0, null);

$lObjectArray = $lTestRestricted->initValue('enumFloatArray');
testSetBadValue($lObjectArray, 0, 1.6);
testUnshiftBadValue($lObjectArray, 3.55);
testPushBadValue($lObjectArray, 3.55);
testPushBadValue($lObjectArray, null);

testSetBadValue($lTestRestricted, 'color', 'rgb(aze,)');
testSetBadValue($lTestRestricted, 'color', null);

$lObjectArray = $lTestRestricted->initValue('emails');
testPushBadValue($lObjectArray, 'azeaze1');
testUnshiftBadValue($lObjectArray, 'azeaze2');
testSetBadValue($lObjectArray, 0, 'azeaze3');
testSetBadValue($lObjectArray, 0, null);

testSetBadValue($lTestRestricted, 'naturalNumber', -4);
testSetBadValue($lTestRestricted, 'naturalNumber', null);

testSetBadValue($lTestRestricted, 'birthDate', new ComhonDateTime('now'));
testSetBadValue($lTestRestricted, 'birthDate', null);

$lObjectArray = $lTestRestricted->initValue('intervalInArray');
testSetBadValue($lObjectArray, 0, 11.6);
testUnshiftBadValue($lObjectArray, -3.55);
testPushBadValue($lObjectArray, -3.55);
testPushBadValue($lObjectArray, null);

/** ********************** test set success *********************** **/

$lTestRestricted->setValue('color', '#12abA8');

$lRegexInArray = $lTestRestricted->getProperty('emails')->getModel()->getObjectInstance();
$lRegexInArray->pushValue('plop.plop@plop.plop');
$lRegexInArray->unshiftValue('plop@plop.fr');
$lTestRestricted->setValue('emails', $lRegexInArray);

$lTestRestricted->setValue('naturalNumber', 45);
$lTestRestricted->setValue('birthDate', new ComhonDateTime('2000-01-01'));

$lRestriction = new Interval(']-1.5, 2[', ModelManager::getInstance()->getInstanceModel('float'));
$lModelRestrictedArray = new ModelRestrictedArray(ModelManager::getInstance()->getInstanceModel('float'), $lRestriction, 'intervalValue');
$lIntervalInArray = new ObjectArray($lModelRestrictedArray);
$lIntervalInArray->pushValue(1);
$lIntervalInArray->unshiftValue(-1.4);
$lTestRestricted->setValue('intervalInArray', $lIntervalInArray);

$lTestRestricted->initValue('enumIntArray');
$lTestRestricted->initValue('enumFloatArray');

$lTest->setValue('enumValue', 'plop1');
$lTest->getValue('enumIntArray')->setValue(0, 1);
$lTest->getValue('enumIntArray')->setValue(1, 3);
$lTest->getValue('enumFloatArray')->pushValue(1.5);
$lTest->getValue('enumFloatArray')->pushValue(3.5);


/** ************** test export import with values not in restriction fail *************** **/

$lTestRestricted->getValue('enumFloatArray')->pushValue(4.5, true, false);

try {
	$lTestRestricted->export($lStdPrivateInterfacer);
	$lThrow = true;
} catch (NotSatisfiedRestrictionException $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new Exception('export with values not in enum');
}

$lTestRestricted->getValue('enumFloatArray')->popValue();
$lTestRestricted->export($lStdPrivateInterfacer);
$lTestRestricted->getValue('enumFloatArray')->unshiftValue(4.5, true, false);

try {
	$lTestRestricted->export($lStdPrivateInterfacer);
	$lThrow = true;
} catch (NotSatisfiedRestrictionException $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new Exception('export with values not in enum');
}
$lTestRestricted->getValue('enumFloatArray')->shiftValue();
$lTestRestricted->export($lStdPrivateInterfacer);

if (!compareJson(json_encode($lTestRestricted->export($lStdPrivateInterfacer)), '{"enumIntArray":[],"enumFloatArray":[],"emails":["plop@plop.fr","plop.plop@plop.plop"],"intervalInArray":[-1.4,1],"color":"#12abA8","naturalNumber":45,"birthDate":"2000-01-01T00:00:00+01:00"}')) {
	throw new Exception('bad value');
}
$lTestRestricted->fillObject(json_decode('{"color":"#12abA8","emails":[],"naturalNumber":45,"birthDate":"2000-01-01T00:00:00+01:00","intervalInArray":[],"enumIntArray":[],"enumFloatArray":[]}'), $lStdPrivateInterfacer);

try {
	$lTestRestricted->fillObject(json_decode('{"color":"#12abA8","emails":[],"naturalNumber":-5,"birthDate":"2000-01-01T00:00:00+01:00","intervalInArray":[],"enumIntArray":[],"enumFloatArray":[]}'), $lStdPrivateInterfacer);
	$lThrow = true;
} catch (NotSatisfiedRestrictionException $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new Exception('export with values not in enum');
}

$time_end = microtime(true);
var_dump('value restriction test exec time '.($time_end - $time_start));
