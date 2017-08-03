<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as Object;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Object\ObjectArray;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);
$stdPublicInterfacer = new StdObjectInterfacer();
$stdPublicInterfacer->setPrivateContext(false);
$xmlPrivateInterfacer = new XMLInterfacer();
$xmlPrivateInterfacer->setPrivateContext(true);
$arrayPrivateInterfacer = new AssocArrayInterfacer();
$arrayPrivateInterfacer->setPrivateContext(true);
$arrayPrivateInterfacer->setFlattenValues(true);

$testDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (!is_null($testDbFromCollection)) {
	throw new \Exception('must be null');
}

/** ****************************** test load new value ****************************** **/

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
/** @var Object $testDb */
$testDb = $dbTestModel->loadObject('[1,"50"]');
$mainParentTestDb = $testDb->getValue('mainParentTestDb');
$object = $testDb->getValue('object');
$objectId = $testDb->getValue('objectWithId');

if ($testDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($testDb->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if (json_encode($testDb->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated value');
}
foreach ($testDb->getProperties() as $property) {
	if ($testDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not have updated value');
	}
}

$testDb->unsetValue('mainParentTestDb');

if (!$testDb->isUpdated()) {
	throw new \Exception('should be updated');
}
if ($testDb->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if (json_encode($testDb->getUpdatedValues()) !== '{"mainParentTestDb":true}') {
	throw new \Exception('should have updated value');
}
foreach ($testDb->getProperties() as $property) {
	if ($property->getName() == 'mainParentTestDb') {
		if (!$testDb->isUpdatedValue($property->getName())) {
			throw new \Exception('should be updated value');
		}
	}
	else if ($testDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not be updated value');
	}
}

$testDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($testDbFromCollection) || $testDbFromCollection !== $testDb) {
	throw new \Exception('null or not same instance');
}

/** ****************************** test load existing value ****************************** **/

$testDb2 = $dbTestModel->loadObject('[1,"50"]');
$mainParentTestDb2 = $testDb2->getValue('mainParentTestDb');
$object2 = $testDb2->getValue('object');
$objectId2 = $testDb2->getValue('objectWithId');

$testDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($testDbFromCollection) || $testDbFromCollection !== $testDb) {
	throw new \Exception('object loaded different than object in ObjectCollection');
}

// $testDb2 must be same instance than $testDb and not modified
if ($testDb !== $testDb2 || !is_null($mainParentTestDb2) || $object !== $object2 || $objectId !== $objectId2) {
	throw new \Exception(' not same object');
}

if (!$testDb->isUpdated()) {
	throw new \Exception('should be updated');
}
if ($testDb->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if (json_encode($testDb->getUpdatedValues()) !== '{"mainParentTestDb":true}') {
	throw new \Exception('should have updated value');
}
foreach ($testDb->getProperties() as $property) {
	if ($property->getName() == 'mainParentTestDb') {
		if (!$testDb->isUpdatedValue($property->getName())) {
			throw new \Exception('should be updated value');
		}
	}
	else if ($testDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not be updated value');
	}
}

/** ****************************** test load existing value and force to reload ****************************** **/

$testDb3 = $dbTestModel->loadObject('[1,"50"]', null, true);
$mainParentTestDb3 = $testDb3->getValue('mainParentTestDb');
$object3 = $testDb3->getValue('object');
$objectId3 = $testDb3->getValue('objectWithId');

$testDbFromCollection = MainObjectCollection::getInstance()->getObject('[1,"50"]', 'testDb');
if (is_null($testDbFromCollection) || $testDbFromCollection !== $testDb) {
	throw new \Exception('object loaded different than object in ObjectCollection');
}

// $testDb3 must be same instance than $testDb with restored 'mainParentTestDb' and not same instance of 'object' due to database reload
if ($testDb !== $testDb3 || $mainParentTestDb !== $mainParentTestDb3 || $object === $object3 || $objectId !== $objectId3) {
	throw new \Exception(' not same object');
}

if ($testDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($testDb->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if (json_encode($testDb->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated value');
}
foreach ($testDb->getProperties() as $property) {
	if ($testDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not be updated value');
	}
}

/** ****************************** test foreign value ****************************** **/

$mainParentTestDb = $testDb->getValue('mainParentTestDb');

if ($mainParentTestDb->isLoaded()) {
	throw new \Exception('foreign value must be unloaded');
}
$testDb->loadValue('mainParentTestDb');

if (!$mainParentTestDb->isLoaded()) {
	throw new \Exception('foreign value must be loaded');
}

if ($testDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($mainParentTestDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($mainParentTestDb->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if (json_encode($mainParentTestDb->getUpdatedValues()) !== '[]') {
	throw new \Exception('should not have updated value');
}
foreach ($mainParentTestDb->getProperties() as $property) {
	if ($mainParentTestDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not be updated value');
	}
}

$id = $mainParentTestDb->getId();
$mainParentTestDb->unsetValue('id');

if (json_encode($mainParentTestDb->getUpdatedValues()) !== '{"id":true}') {
	throw new \Exception('should have id updated value');
}
if (!$mainParentTestDb->isIdUpdated()) {
	throw new \Exception('id should be updated');
}
if (!$testDb->isUpdated()) {
	throw new \Exception('should be updated');
}

try {
	$testDb->export($stdPublicInterfacer);
	$throw = true;
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should not export foreign object without complete id');
}

$mainParentTestDb->setId($id);
$testDb->export($stdPublicInterfacer);

if (!$testDb->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!$testDb->isUpdatedValue('mainParentTestDb')) {
	throw new \Exception('should be updated');
}
if ($testDb->isValueFlagedAsUpdated('mainParentTestDb')) {
	throw new \Exception('should not be flaged as updated');
}
if (!$mainParentTestDb->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!$mainParentTestDb->isIdUpdated()) {
	throw new \Exception('id should be updated');
}
if (json_encode($mainParentTestDb->getUpdatedValues()) !== '{"id":false}') {
	throw new \Exception('should have id updated value');
}
foreach ($mainParentTestDb->getProperties() as $property) {
	if ($property->getName() == 'id') {
		if (!$mainParentTestDb->isUpdatedValue($property->getName())) {
			throw new \Exception('should be updated value');
		}
	}
	else if ($mainParentTestDb->isUpdatedValue($property->getName())) {
		throw new \Exception('should not be updated value');
	}
}

$mainParentTestDb->resetUpdatedStatus();

if ($mainParentTestDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($testDb->isUpdated()) {
	throw new \Exception('should not be updated');
}

/** ****************************** test load ids aggregation value ****************************** **/

$testDbs = MainObjectCollection::getInstance()->getModelObjects('testDb');
$testDbById = [];
foreach ($testDbs as $testDb) {
	$testDbById[$testDb->getId()] = $testDb;
	if ($testDb->getValue('mainParentTestDb') !== $mainParentTestDb) {
		throw new \Exception('foreign value different than existing value');
	}
}
if ($mainParentTestDb->hasValue('childrenTestDb')) {
	throw new \Exception('should not be set');
}
$mainParentTestDb->loadAggregationIds('childrenTestDb');

if (!$mainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new \Exception('foreign value must be loaded');
}
if ($mainParentTestDb->getValue('childrenTestDb')->count() != 6) {
	throw new \Exception('bad children count : '.count($mainParentTestDb->getValue('childrenTestDb')->getValues()));
}
if ($mainParentTestDb->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($mainParentTestDb->isFlagedAsUpdated()) {
	throw new \Exception('should not be updated');
}
if ($mainParentTestDb->getValue('childrenTestDb')->isFlagedAsUpdated()) {
	throw new \Exception('should not be updated');
}
if ($mainParentTestDb->getValue('childrenTestDb')->isIdUpdated()) {
	throw new \Exception('id should not be updated');
}
if ($mainParentTestDb->getValue('childrenTestDb')->isUpdated()) {
	throw new \Exception('should not be updated');
}

foreach ($mainParentTestDb->getValue('childrenTestDb') as $value) {
	if (array_key_exists($value->getId(), $testDbById)) {
		if ($value !== $testDbById[$value->getId()]) {
			throw new \Exception('foreign value different than existing value');
		}
	} else if ($value->isLoaded()) {
		throw new \Exception('foreign value must be unloaded');
	} else if ($mainParentTestDb !== $value->getValue('mainParentTestDb')) {
		throw new \Exception('should be same instance');
	}
}

/** ****************************** test load ids aggregation value ****************************** **/

$testDbs = MainObjectCollection::getInstance()->getModelObjects('testDb');
$testDbById = [];

foreach ($testDbs as $testDb) {
	$testDbById[$testDb->getId()] = $testDb;
	if ($testDb->isLoaded() && $testDb->getValue('mainParentTestDb') !== $mainParentTestDb) {
		throw new \Exception('foreign value different than existing value');
	}
}

$mainParentTestDb->unsetValue('childrenTestDb');
$mainParentTestDb->setValue('childrenTestDb', $mainParentTestDb->getModel()->getproperty('childrenTestDb')->getModel()->getObjectInstance(false));

if ($mainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new \Exception('foreign value must be unloaded');
}
$mainParentTestDb->loadValue('childrenTestDb');

if (!$mainParentTestDb->getValue('childrenTestDb')->isLoaded()) {
	throw new \Exception('foreign value must be loaded');
}
if (count($mainParentTestDb->getValue('childrenTestDb')->getValues()) != count($testDbById)) {
	throw new \Exception('different children count');
}

foreach ($mainParentTestDb->getValue('childrenTestDb') as $value) {
	if (!array_key_exists($value->getId(), $testDbById)) {
		throw new \Exception('child must be already existing');
	}
	if ($value !== $testDbById[$value->getId()]) {
		throw new \Exception('foreign value different than existing value');
	}
	if (!$value->isLoaded()) {
		throw new \Exception('foreign value must be loaded');
	}
}

/** ****************************** test default values ****************************** **/

$testModel = ModelManager::getInstance()->getInstanceModel('test');
$test = $testModel->getObjectInstance();
$test->initValue('objectValue');

if (!compareJson(json_encode($test->export($stdPublicInterfacer)), '{"stringValue":"plop","floatValue":1.5,"booleanValue":true,"indexValue":0,"percentageValue":1,"dateValue":"2016-11-13T20:04:05+01:00","objectValue":{"stringValue":"plop2","booleanValue":false}}')) {
	throw new \Exception('not good default values');
}

/** ****************************** test enum values ****************************** **/

$test->initValue('enumIntArray');
$test->initValue('enumFloatArray');

$test->setValue('enumValue', 'plop1');
$test->getValue('enumIntArray')->setValue(0, 1);
$test->getValue('enumIntArray')->setValue(1, 3);
$test->getValue('enumFloatArray')->pushValue(1.5);
$test->getValue('enumFloatArray')->pushValue(3.5);

/** ****************************** test import with no merge and reference to root object ****************************** **/

$test->setId('plopplop');
try {
	MainObjectCollection::getInstance()->addObject($test);
	$throw = true;
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('should be already added');
}

$objectRefParent = $test->initValue('objectRefParent');
$objectRefParent->setValue('name', 'hahahahaha');
$objectRefParent->setValue('parent', $test);

$test2 = $testModel->import($test->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if ($test2 !== $test || $test2 !== $test2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$stdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$test3 = $testModel->import($test->export($stdPrivateInterfacer), $stdPrivateInterfacer);
if ($test3 === $test) {
	throw new \Exception('same instance');
}
if ($test === $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($test3 !== $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($test !== MainObjectCollection::getInstance()->getObject($test->getId(), $test->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$test2 = $testModel->import($test->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if ($test2 !== $test || $test2 !== $test2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$xmlPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$test3 = $testModel->import($test->export($xmlPrivateInterfacer), $xmlPrivateInterfacer);
if ($test3 === $test) {
	throw new \Exception('same instance');
}
if ($test === $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($test3 !== $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($test !== MainObjectCollection::getInstance()->getObject($test->getId(), $test->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$test2 = $testModel->import($test->export($arrayPrivateInterfacer), $arrayPrivateInterfacer);
if ($test2 !== $test || $test2 !== $test2->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}
$arrayPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$test3 = $testModel->import($test->export($arrayPrivateInterfacer), $arrayPrivateInterfacer);
if ($test3 === $test) {
	throw new \Exception('same instance');
}
if ($test === $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('same instance');
}
if ($test3 !== $test3->getValue('objectRefParent')->getValue('parent')) {
	throw new \Exception('not same instance');
}

if ($test !== MainObjectCollection::getInstance()->getObject($test->getId(), $test->getModel()->getName())) {
	throw new \Exception('not same instance');
}

$id = 'plopplop';
if ($test->getId() !== $id) {
	throw new \Exception('not good id');
}
$newId = 'hehe';
$test->setId($newId);
if ($test->getId() !== $newId) {
	throw new \Exception('id not updated');
}
if (!is_null(MainObjectCollection::getInstance()->getObject($id, $test->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
if (is_null(MainObjectCollection::getInstance()->getObject($newId, $test->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
$test->setId($id);
if ($test->getId() !== $id) {
	throw new \Exception('id not updated');
}
if (is_null(MainObjectCollection::getInstance()->getObject($id, $test->getModel()->getName()))) {
	throw new \Exception('object not moved');
}
if (!is_null(MainObjectCollection::getInstance()->getObject($newId, $test->getModel()->getName()))) {
	throw new \Exception('object not moved');
}

/** ********* test import main foreign value not in singleton MainObjectCollection ********** **/

$mainTestModel = ModelManager::getInstance()->getInstanceModel('mainTestDb');
$mainTestDb = $mainTestModel->getObjectInstance();
$mainTestDb->setId(4287);
MainObjectCollection::getInstance()->removeObject($mainTestDb);

$testModel = ModelManager::getInstance()->getInstanceModel('testDb');
$testDb = $testModel->getObjectInstance();
$testDb->setId('[4567,"74107"]');
$testDb->setValue('mainParentTestDb', $mainTestDb);

$testDb->fill($testDb->export($stdPrivateInterfacer), $stdPrivateInterfacer);

if ($mainTestDb !== $testDb->getValue('mainParentTestDb')) {
	throw new \Exception('bad object instance');
}

/** ********* idem with object array ******* **/

$mainTestDb2 = $mainTestModel->getObjectInstance();
$mainTestDb2->setId(8541);

$array = new ObjectArray($mainTestModel);
$array->pushValue($mainTestDb);
$array->pushValue($mainTestDb2);

MainObjectCollection::getInstance()->removeObject($mainTestDb2);
MainObjectCollection::getInstance()->removeObject($mainTestDb);
$stdPrivateInterfacer->setMergeType(Interfacer::MERGE);
$array->fill($array->export($stdPrivateInterfacer), $stdPrivateInterfacer);

if ($mainTestDb !== $array->getValue(0) || $mainTestDb2 !== $array->getValue(1)) {
	throw new \Exception('bad object instance');
}

// add new instance but with existing id (8541) in MainObjectCollection
MainObjectCollection::getInstance()->removeObject($mainTestDb2);
$mainTestDb3 = $mainTestModel->getObjectInstance();
$mainTestDb3->setId(8541);
if (MainObjectCollection::getInstance()->getObject(8541, 'mainTestDb') !== $mainTestDb3) {
	throw new \Exception('bad object instance');
}

MainObjectCollection::getInstance()->removeObject($mainTestDb);
$stdPrivateInterfacer->setMergeType(Interfacer::OVERWRITE);
$array->fill($array->export($stdPrivateInterfacer), $stdPrivateInterfacer);

if ($mainTestDb !== $array->getValue(0) || $mainTestDb2 !== $array->getValue(1)) {
	throw new \Exception('bad object instance');
}

MainObjectCollection::getInstance()->removeObject($mainTestDb2);
MainObjectCollection::getInstance()->removeObject($mainTestDb);
$stdPrivateInterfacer->setMergeType(Interfacer::NO_MERGE);
$array->fill($array->export($stdPrivateInterfacer), $stdPrivateInterfacer);

if ($mainTestDb === $array->getValue(0) || $mainTestDb2 === $array->getValue(1)) {
	throw new \Exception('bad object instance');
}

/** ****************************** test values of local model defined in distant manifest *********************************** **/

$testXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$testXml = $testXmlModel->getObjectInstance();

$testXml->initValue('objectContainer')
		->initValue('person')
		->initValue('recursiveLocal')
		->initValue('anotherObjectWithIdAndMore')
		->setValue('plop3', 'hahahaha');

$interfacer = new StdObjectInterfacer();
$interfacer->setPrivateContext(true);
if (!compareJson(json_encode($testXml->export($interfacer)), '{"objectContainer":{"person":{"recursiveLocal":{"anotherObjectWithIdAndMore":{"plop3":"hahahaha"}}}}}')) {
	throw new \Exception('bad value');
}


$time_end = microtime(true);
var_dump('value test exec time '.($time_end - $time_start));
