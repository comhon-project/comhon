<?php

use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\api\ObjectService;
use comhon\object\object\SqlTable;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;
use comhon\object\object\ObjectArray;
use comhon\object\model\ModelArray;

$time_start = microtime(true);

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (!is_null($lTestDbFromCollection)) {
	throw new Exception('must be null');
}

/** ****************************** test load new value ****************************** **/

$lTestDb = $lDbTestModel->loadObject('[1,"50"]');
$lMainParentTestDb = $lTestDb->getValue('mainParentTestDb');
$lObject = $lTestDb->getValue('object');
$lObjectId = $lTestDb->getValue('objectWithId');
$lTestDb->deleteValue('mainParentTestDb');

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($lTestDbFromCollection) || $lTestDbFromCollection !== $lTestDb) {
	throw new Exception('null or not same instance');
}

/** ****************************** test load existing value ****************************** **/

$lTestDb2 = $lDbTestModel->loadObject('["1",50]');
$lMainParentTestDb2 = $lTestDb2->getValue('mainParentTestDb');
$lObject2 = $lTestDb2->getValue('object');
$lObjectId2 = $lTestDb2->getValue('objectWithId');

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($lTestDbFromCollection) || $lTestDbFromCollection !== $lTestDb) {
	throw new Exception('object loaded different than object in ObjectCollection');
}

// $lTestDb2 must be same instance than $lTestDb and not modified
if ($lTestDb !== $lTestDb2 || !is_null($lMainParentTestDb2) || $lObject !== $lObject2 || $lObjectId !== $lObjectId2) {
	throw new \Exception(' not same object');
}

/** ****************************** test load existing value and force to reload ****************************** **/

$lTestDb3 = $lDbTestModel->loadObject('["1","50"]', true);
$lMainParentTestDb3 = $lTestDb3->getValue('mainParentTestDb');
$lObject3 = $lTestDb3->getValue('object');
$lObjectId3 = $lTestDb3->getValue('objectWithId');

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($lTestDbFromCollection) || $lTestDbFromCollection !== $lTestDb) {
	throw new Exception('object loaded different than object in ObjectCollection');
}

// $lTestDb3 must be same instance than $lTestDb with resoted 'mainParentTestDb' and not same instance of 'object' due to database reload
if ($lTestDb !== $lTestDb3 || $lMainParentTestDb !== $lMainParentTestDb3 || $lObject === $lObject3 || $lObjectId !== $lObjectId3) {
	throw new \Exception(' not same object');
}

/** ****************************** test foreign value ****************************** **/

$lMainParentTestDb = $lTestDb->getValue('mainParentTestDb');

if ($lMainParentTestDb->isLoaded()) {
	throw new Exception('foreign value must be unloaded');
}
$lTestDb->loadValue('mainParentTestDb');

if (!$lMainParentTestDb->isLoaded()) {
	throw new Exception('foreign value must be loaded');
}

/** ****************************** test load ids composition value ****************************** **/

$lTestDbs = MainObjectCollection::getInstance()->getModelObjects('testDb');
$lTestDbById = [];

foreach ($lTestDbs as $lTestDb) {
	$lTestDbById[$lTestDb->getId()] = $lTestDb;
	if ($lTestDb->getValue('mainParentTestDb') !== $lMainParentTestDb) {
		throw new Exception('foreign value different than existing value');
	}
}

if ($lMainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new Exception('foreign value must be unloaded');
}
$lMainParentTestDb->loadValueIds('childrenTestDb');

if (!$lMainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new Exception('foreign value must be loaded');
}
if (count($lMainParentTestDb->getValue('childrenTestDb')->getValues()) != 6) {
	throw new Exception('bad children count');
}

foreach ($lMainParentTestDb->getValue('childrenTestDb')->getValues() as $lValue) {
	if (array_key_exists($lValue->getId(), $lTestDbById)) {
		if ($lValue !== $lTestDbById[$lValue->getId()]) {
			throw new Exception('foreign value different than existing value');
		}
	} else if ($lValue->isLoaded()) {
		throw new Exception('foreign value must be unloaded');
	}
}

/** ****************************** test load ids composition value ****************************** **/

$lTestDbs = MainObjectCollection::getInstance()->getModelObjects('testDb');
$lTestDbById = [];

foreach ($lTestDbs as $lTestDb) {
	$lTestDbById[$lTestDb->getId()] = $lTestDb;
	if ($lTestDb->isLoaded() && $lTestDb->getValue('mainParentTestDb') !== $lMainParentTestDb) {
		throw new Exception('foreign value different than existing value');
	}
}

$lMainParentTestDb->deleteValue('childrenTestDb');
$lMainParentTestDb->setValue('childrenTestDb', $lMainParentTestDb->getModel()->getpropertyModel('childrenTestDb')->getObjectInstance(false));

if ($lMainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new Exception('foreign value must be unloaded');
}
$lMainParentTestDb->loadValue('childrenTestDb');

if (!$lMainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new Exception('foreign value must be loaded');
}
if (count($lMainParentTestDb->getValue('childrenTestDb')->getValues()) != count($lTestDbById)) {
	throw new Exception('different children count');
}

foreach ($lMainParentTestDb->getValue('childrenTestDb')->getValues() as $lValue) {
	if (!array_key_exists($lValue->getId(), $lTestDbById)) {
		throw new Exception('child must be already existing');
	}
	if ($lValue !== $lTestDbById[$lValue->getId()]) {
		throw new Exception('foreign value different than existing value');
	}
	if (!$lValue->isLoaded()) {
		throw new Exception('foreign value must be loaded');
	}
}

/** ****************************** test default values ****************************** **/

$lTestModel = InstanceModel::getInstance()->getInstanceModel('test');
$lTest = $lTestModel->getObjectInstance();
$lTest->initValue('objectValue');

if (json_encode($lTest->toStdObject()) !== '{"stringValue":"plop","floatValue":1.5,"booleanValue":true,"dateValue":"2016-11-13T20:04:05+01:00","objectValue":{"stringValue":"plop2","booleanValue":false}}') {
	throw new Exception('not good default values');
}

/** ****************************** test enum values ****************************** **/

try {
	$lTest->setValue('enumValue', 'haha');
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('set value with bad enum value should\'t work');
}

try {
	$lTest->setValue('enumValue', true);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('set value with bad enum value should\'t work');
}

$lObjectArray = $lTest->initValue('enumIntArray');
try {
	$lObjectArray->pushValue(10);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('set value with bad enum value should\'t work');
}

$lObjectArray = $lTest->initValue('enumFloatArray');
try {
	$lObjectArray->setValue(0, 1.6);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('set value with bad enum value should\'t work');
}

$lTest->setValue('enumValue', 'plop1');
$lTest->getValue('enumIntArray')->setValue(0, 1);
$lTest->getValue('enumIntArray')->setValue(1, 3);
$lTest->getValue('enumFloatArray')->pushValue(1.5);
$lTest->getValue('enumFloatArray')->pushValue(3.5);
$lTest->getValue('enumFloatArray')->pushValue(4.5, false);

$time_end = microtime(true);
var_dump('value test exec time '.($time_end - $time_start));