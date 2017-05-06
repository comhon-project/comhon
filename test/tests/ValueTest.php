<?php

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\collection\MainObjectCollection;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\Interfacer;
use comhon\interfacer\AssocArrayInterfacer;
use comhon\object\ObjectArray;

$time_start = microtime(true);

$lStdPrivateInterfacer = new StdObjectInterfacer();
$lStdPrivateInterfacer->setPrivateContext(true);
$lStdPublicInterfacer = new StdObjectInterfacer();
$lStdPublicInterfacer->setPrivateContext(false);
$lXmlPrivateInterfacer = new XMLInterfacer();
$lXmlPrivateInterfacer->setPrivateContext(true);
$lArrayPrivateInterfacer = new AssocArrayInterfacer();
$lArrayPrivateInterfacer->setPrivateContext(true);
$lArrayPrivateInterfacer->setFlattenValues(true);

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (!is_null($lTestDbFromCollection)) {
	throw new Exception('must be null');
}

/** ****************************** test load new value ****************************** **/

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
/** @var Object $lTestDb */
$lTestDb = $lDbTestModel->loadObject('[1,"50"]');
$lMainParentTestDb = $lTestDb->getValue('mainParentTestDb');
$lObject = $lTestDb->getValue('object');
$lObjectId = $lTestDb->getValue('objectWithId');

if ($lTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lTestDb->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if (json_encode($lTestDb->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated value');
}
foreach ($lTestDb->getProperties() as $lProperty) {
	if ($lTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not have updated value');
	}
}

$lTestDb->deleteValue('mainParentTestDb');

if (!$lTestDb->isUpdated()) {
	throw new Exception('should be updated');
}
if ($lTestDb->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if (json_encode($lTestDb->getUpdatedValues()) !== '{"mainParentTestDb":true}') {
	throw new Exception('should have updated value');
}
foreach ($lTestDb->getProperties() as $lProperty) {
	if ($lProperty->getName() == 'mainParentTestDb') {
		if (!$lTestDb->isUpdatedValue($lProperty->getName())) {
			throw new Exception('should be updated value');
		}
	}
	else if ($lTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not be updated value');
	}
}

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($lTestDbFromCollection) || $lTestDbFromCollection !== $lTestDb) {
	throw new Exception('null or not same instance');
}

/** ****************************** test load existing value ****************************** **/

$lTestDb2 = $lDbTestModel->loadObject('[1,"50"]');
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

if (!$lTestDb->isUpdated()) {
	throw new Exception('should be updated');
}
if ($lTestDb->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if (json_encode($lTestDb->getUpdatedValues()) !== '{"mainParentTestDb":true}') {
	throw new Exception('should have updated value');
}
foreach ($lTestDb->getProperties() as $lProperty) {
	if ($lProperty->getName() == 'mainParentTestDb') {
		if (!$lTestDb->isUpdatedValue($lProperty->getName())) {
			throw new Exception('should be updated value');
		}
	}
	else if ($lTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not be updated value');
	}
}

/** ****************************** test load existing value and force to reload ****************************** **/

$lTestDb3 = $lDbTestModel->loadObject('[1,"50"]', null, true);
$lMainParentTestDb3 = $lTestDb3->getValue('mainParentTestDb');
$lObject3 = $lTestDb3->getValue('object');
$lObjectId3 = $lTestDb3->getValue('objectWithId');

$lTestDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($lTestDbFromCollection) || $lTestDbFromCollection !== $lTestDb) {
	throw new Exception('object loaded different than object in ObjectCollection');
}

// $lTestDb3 must be same instance than $lTestDb with restored 'mainParentTestDb' and not same instance of 'object' due to database reload
if ($lTestDb !== $lTestDb3 || $lMainParentTestDb !== $lMainParentTestDb3 || $lObject === $lObject3 || $lObjectId !== $lObjectId3) {
	throw new \Exception(' not same object');
}

if ($lTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lTestDb->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if (json_encode($lTestDb->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated value');
}
foreach ($lTestDb->getProperties() as $lProperty) {
	if ($lTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not be updated value');
	}
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

if ($lTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lMainParentTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lMainParentTestDb->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if (json_encode($lMainParentTestDb->getUpdatedValues()) !== '[]') {
	throw new Exception('should not have updated value');
}
foreach ($lMainParentTestDb->getProperties() as $lProperty) {
	if ($lMainParentTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not be updated value');
	}
}

$lId = $lMainParentTestDb->getId();
$lMainParentTestDb->deleteValue('id');

if (json_encode($lMainParentTestDb->getUpdatedValues()) !== '{"id":true}') {
	throw new Exception('should have id updated value');
}
if (!$lMainParentTestDb->isIdUpdated()) {
	throw new Exception('id should be updated');
}
if (!$lTestDb->isUpdated()) {
	throw new Exception('should be updated');
}

try {
	$lTestDb->export($lStdPublicInterfacer);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new Exception('should not export foreign object without complete id');
}

$lMainParentTestDb->setId($lId);
$lTestDb->export($lStdPublicInterfacer);

if (!$lTestDb->isUpdated()) {
	throw new Exception('should be updated');
}
if (!$lTestDb->isUpdatedValue('mainParentTestDb')) {
	throw new Exception('should be updated');
}
if ($lTestDb->isValueFlagedAsUpdated('mainParentTestDb')) {
	throw new Exception('should not be flaged as updated');
}
if (!$lMainParentTestDb->isUpdated()) {
	throw new Exception('should be updated');
}
if (!$lMainParentTestDb->isIdUpdated()) {
	throw new Exception('id should be updated');
}
if (json_encode($lMainParentTestDb->getUpdatedValues()) !== '{"id":false}') {
	throw new Exception('should have id updated value');
}
foreach ($lMainParentTestDb->getProperties() as $lProperty) {
	if ($lProperty->getName() == 'id') {
		if (!$lMainParentTestDb->isUpdatedValue($lProperty->getName())) {
			throw new Exception('should be updated value');
		}
	}
	else if ($lMainParentTestDb->isUpdatedValue($lProperty->getName())) {
		throw new Exception('should not be updated value');
	}
}

$lMainParentTestDb->resetUpdatedStatus();

if ($lMainParentTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}

/** ****************************** test load ids aggregation value ****************************** **/

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
	throw new Exception('bad children count : '.count($lMainParentTestDb->getValue('childrenTestDb')->getValues()));
}
if ($lMainParentTestDb->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lMainParentTestDb->isFlagedAsUpdated()) {
	throw new Exception('should not be updated');
}
if ($lMainParentTestDb->getValue('childrenTestDb')->isFlagedAsUpdated()) {
	throw new Exception('should not be updated');
}
if ($lMainParentTestDb->getValue('childrenTestDb')->isIdUpdated()) {
	throw new Exception('id should not be updated');
}
if ($lMainParentTestDb->getValue('childrenTestDb')->isUpdated()) {
	throw new Exception('should not be updated');
}

foreach ($lMainParentTestDb->getValue('childrenTestDb')->getValues() as $lValue) {
	if (array_key_exists($lValue->getId(), $lTestDbById)) {
		if ($lValue !== $lTestDbById[$lValue->getId()]) {
			throw new Exception('foreign value different than existing value');
		}
	} else if ($lValue->isLoaded()) {
		throw new Exception('foreign value must be unloaded');
	} else if ($lMainParentTestDb !== $lValue->getValue('mainParentTestDb')) {
		throw new Exception('should be same instance');
	}
}

/** ****************************** test load ids aggregation value ****************************** **/

$lTestDbs = MainObjectCollection::getInstance()->getModelObjects('testDb');
$lTestDbById = [];

foreach ($lTestDbs as $lTestDb) {
	$lTestDbById[$lTestDb->getId()] = $lTestDb;
	if ($lTestDb->isLoaded() && $lTestDb->getValue('mainParentTestDb') !== $lMainParentTestDb) {
		throw new Exception('foreign value different than existing value');
	}
}

$lMainParentTestDb->deleteValue('childrenTestDb');
$lMainParentTestDb->setValue('childrenTestDb', $lMainParentTestDb->getModel()->getproperty('childrenTestDb')->getModel()->getObjectInstance(false));

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

$lTestModel = ModelManager::getInstance()->getInstanceModel('test');
$lTest = $lTestModel->getObjectInstance();
$lTest->initValue('objectValue');

if (!compareJson(json_encode($lTest->export($lStdPublicInterfacer)), '{"stringValue":"plop","floatValue":1.5,"booleanValue":true,"dateValue":"2016-11-13T20:04:05+01:00","objectValue":{"stringValue":"plop2","booleanValue":false}}')) {
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
$lTest->getValue('enumFloatArray')->pushValue(4.5, true, false);

/** ****************************** test import with no merge and reference to root object ****************************** **/

$lTest->setId('plopplop');
try {
	MainObjectCollection::getInstance()->addObject($lTest);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new Exception('should be already added');
}

$lObjectRefParent = $lTest->initValue('objectRefParent');
$lObjectRefParent->setValue('name', 'hahahahaha');
$lObjectRefParent->setValue('parent', $lTest);

$lTest2 = $lTestModel->import($lTest->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if ($lTest2 !== $lTest || $lTest2 !== $lTest2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$lStdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lTest3 = $lTestModel->import($lTest->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);
if ($lTest3 === $lTest) {
	throw new \Exception('same instance');
}
if ($lTest === $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($lTest3 !== $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($lTest !== MainObjectCollection::getInstance()->getObject($lTest->getId(), $lTest->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$lTest2 = $lTestModel->import($lTest->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if ($lTest2 !== $lTest || $lTest2 !== $lTest2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$lXmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lTest3 = $lTestModel->import($lTest->export($lXmlPrivateInterfacer), $lXmlPrivateInterfacer);
if ($lTest3 === $lTest) {
	throw new \Exception('same instance');
}
if ($lTest === $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($lTest3 !== $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($lTest !== MainObjectCollection::getInstance()->getObject($lTest->getId(), $lTest->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$lTest2 = $lTestModel->import($lTest->export($lArrayPrivateInterfacer), $lArrayPrivateInterfacer);
if ($lTest2 !== $lTest || $lTest2 !== $lTest2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$lArrayPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lTest3 = $lTestModel->import($lTest->export($lArrayPrivateInterfacer), $lArrayPrivateInterfacer);
if ($lTest3 === $lTest) {
	throw new \Exception('same instance');
}
if ($lTest === $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($lTest3 !== $lTest3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($lTest !== MainObjectCollection::getInstance()->getObject($lTest->getId(), $lTest->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$lId = 'plopplop';
if ($lTest->getId() !== $lId) {
	throw new \Exception('not good id');
}
$lNewId = 'hehe';
$lTest->setId($lNewId);
if ($lTest->getId() !== $lNewId) {
	throw new \Exception('id not updated');
}
if (!is_null(MainObjectCollection::getInstance()->getObject($lId, $lTest->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
if (is_null(MainObjectCollection::getInstance()->getObject($lNewId, $lTest->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
$lTest->setId($lId);
if ($lTest->getId() !== $lId) {
	throw new \Exception('id not updated');
}
if (is_null(MainObjectCollection::getInstance()->getObject($lId, $lTest->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
if (!is_null(MainObjectCollection::getInstance()->getObject($lNewId, $lTest->getModel()->getName()))) {
	throw new \Exception('object not moved');
}

/** ********* test import main foreign value not in singleton MainObjectCollection ********** **/

$lMainTestModel = ModelManager::getInstance()->getInstanceModel('mainTestDb');
$lMainTestDb = $lMainTestModel->getObjectInstance();
$lMainTestDb->setId(4287);
MainObjectCollection::getInstance()->removeObject($lMainTestDb);

$lTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lTestDb = $lTestModel->getObjectInstance();
$lTestDb->setId('[4567,"74107"]');
$lTestDb->setValue('mainParentTestDb', $lMainTestDb);

$lTestDb->fillObject($lTestDb->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);

if ($lMainTestDb !== $lTestDb->getValue('mainParentTestDb')) {
	throw new \Exception('bad object instance');
}

/** ********* idem with object array ******* **/

$lMainTestDb2 = $lMainTestModel->getObjectInstance();
$lMainTestDb2->setId(8541);

$lArray = new ObjectArray($lMainTestModel);
$lArray->pushValue($lMainTestDb);
$lArray->pushValue($lMainTestDb2);

MainObjectCollection::getInstance()->removeObject($lMainTestDb2);
MainObjectCollection::getInstance()->removeObject($lMainTestDb);
$lStdPrivateInterfacer->setMergeType(Interfacer::MERGE);
$lArray->fillObject($lArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);

if ($lMainTestDb !== $lArray->getValue(0)) {
	throw new \Exception('bad object instance');
}

MainObjectCollection::getInstance()->removeObject($lMainTestDb2);
MainObjectCollection::getInstance()->removeObject($lMainTestDb);
$lStdPrivateInterfacer->setMergeType(Interfacer::OVERWRITE);
$lArray->fillObject($lArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);

if ($lMainTestDb !== $lArray->getValue(0)) {
	throw new \Exception('bad object instance');
}

MainObjectCollection::getInstance()->removeObject($lMainTestDb2);
MainObjectCollection::getInstance()->removeObject($lMainTestDb);
$lStdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$lArray->fillObject($lArray->export($lStdPrivateInterfacer), $lStdPrivateInterfacer);

if ($lMainTestDb === $lArray->getValue(0)) {
	throw new \Exception('bad object instance');
}

$time_end = microtime(true);
var_dump('value test exec time '.($time_end - $time_start));