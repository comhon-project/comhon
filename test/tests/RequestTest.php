<?php

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\_final\Object as FinalObject;
use comhon\api\ObjectService;
use comhon\serialization\SqlTable;
use comhon\object\collection\MainObjectCollection;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;

$time_start = microtime(true);


/** ****************************** test request objects ****************************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"properties" : ["date","timestamp","integer","string"],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : ["aaaa","cccc","bbbbsdfsdfsdf"]
			},
			{
				"model"    : "testDb",
				"property" : "boolean2",
				"operator" : "=",
				"value"    : true
			}
		]
	}
}';

$lResult = ObjectService::getObjects(json_decode($Json), true);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success || !isset($lResult->result) || !is_array($lResult->result)) {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

if (!compareJson(json_encode($lResult->result), '[{"date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"id1":1,"id2":"23"},{"date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":2,"id1":1,"id2":"101"}]')) {
	var_dump(json_encode($lResult->result));
	throw new Exception('bad objects : '.json_encode($lResult->result));
}

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : ["aaaa","cccc","bbbbsdfsdfsdf"]
			},
			{
				"model"    : "testDb",
				"property" : "boolean2",
				"operator" : "=",
				"value"    : true
			}
		]
	}
}';

$lResult = ObjectService::getObjects(json_decode($Json), true);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success || !isset($lResult->result) || !is_array($lResult->result)) {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

if (!compareJson(json_encode($lResult->result), '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":2,"object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]')) {
	var_dump(json_encode($lResult->result));
	throw new Exception('bad objects : '.json_encode($lResult->result));
}

MainObjectCollection::getInstance()->getObject('[1,"23"]', 'testDb')->reorderValues();
MainObjectCollection::getInstance()->getObject('[1,"101"]', 'testDb')->reorderValues();

/** ****************************** test following export import objects ****************************** **/

$lBasedObjects  = [
	json_decode('{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}'),
	json_decode('{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}')
];

$lStdPrivateInterfacer = new StdObjectInterfacer();
$lStdPrivateInterfacer->setInterfacePrivateProperties(true);
$lStdPublicInterfacer = new StdObjectInterfacer();
$lStdPublicInterfacer->setInterfacePrivateProperties(false);

$lXmlSerialInterfacer = new XMLInterfacer();
$lXmlSerialInterfacer->setInterfacePrivateProperties(false);
$lXmlSerialInterfacer->setSerialContext(true);

$lObject = null;
foreach ($lResult->result as $lIndex => $lStdObject) {
	$lObject = new FinalObject('testDb');
	try {
		$lObject->fillObject($lStdObject, $lStdPrivateInterfacer);
		$lThrow = true;
	} catch (Exception $e) {
		$lThrow = false;
	}
	if ($lThrow) {
		throw new Exception('import should works other instance already exists');
	}
	$lId1 = $lStdObject->id1;
	$lId2 = $lStdObject->id2;
	unset($lStdObject->id1);
	unset($lStdObject->id2);
	
	$lObject = new FinalObject('testDb');
	$lObject->fillObject($lStdObject, $lStdPrivateInterfacer);

	$lObject2 = new FinalObject('testDb');
	$lObject2->fillObject($lObject->export($lXmlSerialInterfacer), $lXmlSerialInterfacer);
	$lObject2->setValue('id1', $lId1);
	$lObject2->setValue('id2', $lId2);
	
	if (!compareJson(json_encode($lObject2->export($lStdPrivateInterfacer)), json_encode($lBasedObjects[$lIndex]))) {
		throw new Exception('bad object');
	}
}

/** *************** test DateTime/DateTimeZone and unserializable value with database serialization ****************** **/

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');

/** @var Object $lObject */
$lObject = $lDbTestModel->loadObject('[1,1501774389]');
$lObjectJson = $lObject->export($lStdPrivateInterfacer);

$lPublicObjectJson = $lObject->export($lStdPublicInterfacer);

if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lObject->isUpdatedValue('timestamp')) {
	throw new Exception('should not be updated');
}
if ($lObject->getValue('timestamp')->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lObject->save(SqlTable::UPDATE) !== 0) {
	throw new \Exception('serialization should return 0 because there is no update');
}
// update dateTime
$lObject->getValue('timestamp')->sub(new DateInterval('P0Y0M0DT5H0M0S'));
if (!$lObject->isUpdated()) {
	throw new Exception('should be updated');
}
if (!$lObject->isUpdatedValue('timestamp')) {
	throw new Exception('should be updated');
}
if (!$lObject->getValue('timestamp')->isUpdated()) {
	throw new Exception('should be updated');
}

if ($lObject->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be updated');
}
if ($lObject->isUpdatedValue('timestamp')) {
	throw new Exception('should not be updated');
}
if ($lObject->getValue('timestamp')->isUpdated()) {
	throw new Exception('should not be updated');
}

foreach ($lObject->getValues() as $lName => $lValue) {
	$lObject->flagValueAsUpdated($lName);
}
if ($lObject->save(SqlTable::UPDATE) !== 0) {
	throw new \Exception('serialization should return 0 because there is no update IN database');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lObject = $lDbTestModel->loadObject('[1,1501774389]', null, true);
$lObject->getValue('timestamp')->add(new DateInterval('P0Y0M0DT5H0M0S'));
$lObject->setValue('notSerializedValue', 'azezaeaze');
$lObject->setValue('notSerializedForeignObject', $lObject->getValue('lonelyForeignObject'));
foreach ($lObject->getValues() as $lName => $lValue) {
	$lObject->flagValueAsUpdated($lName);
}
if ($lObject->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lObject->deleteValue('notSerializedValue');
$lObject->deleteValue('notSerializedForeignObject');
$lObject->resetUpdatedStatus();

$lObject = $lDbTestModel->loadObject('[1,1501774389]', [], true);

if (!compareJson(json_encode($lObject->export($lStdPrivateInterfacer)), json_encode($lObjectJson))) {
	throw new Exception('bad object');
}

/** ************************* test deleted values with database serialization ************************ **/

$lValue = $lObject->getValue('integer');
$lObject->deleteValue('integer');

$lObject->save(SqlTable::UPDATE);
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lObject = $lDbTestModel->loadObject('[1,1501774389]', null, true);
if (!$lObject->hasValue('integer') || !is_null($lObject->getValue('integer'))) {
	throw new Exception('should not have integer value');
}
$lObject->setValue('integer', $lValue);
$lObject->reorderValues();

$lObject->save(SqlTable::UPDATE);
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

/** ****************************** test simple load request api ****************************** **/

/** ************* new object with filter ************** **/

MainObjectCollection::getInstance()->removeObject(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'));

$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lParams->properties = ['date','timestamp','integer','string'];
$lResult = ObjectService::getObject($lParams, true);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed : '.json_encode($lResult));
}
if (!compareJson(json_encode($lResult->result), '{"date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2,"id1":1,"id2":"1501774389"}')) {
	throw new Exception('bad object : '.json_encode($lResult->result));
}

/** ************* existing object partial object ************** **/

$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lResult = ObjectService::getObject($lParams);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed');
}
if (!compareJson(json_encode($lResult->result), '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2}')) {
	throw new Exception('bad object : '.json_encode($lResult->result));
}

/** ************* new object full object ************** **/

MainObjectCollection::getInstance()->removeObject(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'));
$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lResult = ObjectService::getObject($lParams);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed');
}
if (!compareJson(json_encode($lResult->result), '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","boolean":false,"boolean2":true}')) {
	throw new Exception('bad object : '.json_encode($lResult->result));
}

/** ************* existing full object with filter ************** **/

$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lParams->properties = ['date','timestamp','integer','string'];
$lResult = ObjectService::getObject($lParams, true);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed');
}
if (!compareJson(json_encode($lResult->result), '{"date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2,"id1":1,"id2":"1501774389"}')) {
	throw new Exception('bad object : '.json_encode($lResult->result));
}

/** ************* existing full object reodered ************** **/

MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb')->reorderValues();
$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lResult = ObjectService::getObject($lParams);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed');
}
if (!compareJson(json_encode($lResult->result), json_encode($lPublicObjectJson))) {
	var_dump(json_encode($lPublicObjectJson));
	throw new Exception('bad object : '.json_encode($lResult->result));
}

$lTestDb = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$lTestDb->loadValue('childrenTestDb', ['id']);

if (!compareJson(json_encode($lTestDb->getValue('childrenTestDb')->export($lStdPrivateInterfacer)), '[{"id":1},{"id":2}]')) {
	var_dump(json_encode($lPublicObjectJson));
	throw new Exception('bad object : '.json_encode($lTestDb->getValue('childrenTestDb')->export($lStdPrivateInterfacer)));
}

$lChildren = $lTestDb->getValue('childrenTestDb');
$lTestDb1 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$lTestDb->reset();
$lTestDb2 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$lTestDb->setIsLoaded(false);
$lTestDb->setId('[1,"1501774389"]', false);
$lTestDb3 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');

if (!is_null($lTestDb2)) {
	throw new Exception('should be null');
}
if (is_null($lTestDb3)) {
	throw new Exception('should be not null');
}
if ($lTestDb !== $lTestDb1 || $lTestDb1 !== $lTestDb3) {
	throw new Exception('should be same instance');
}
$lTestDb->deleteValue('id2');
if (!is_null(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'))) {
	throw new Exception('should be null');
}
$lTestDb->setValue('id2', '1501774389');
if ($lTestDb !== MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb')) {
	throw new Exception('should be same instance');
}

$lTestDb->setValue('childrenTestDb', $lChildren, false);
$lTestDb->getValue('childrenTestDb')->getValue(0)->setValue('parentTestDb', $lTestDb);
$lTestDb->getValue('childrenTestDb')->getValue(0)->loadValue('parentTestDb', ['integer']);

if (!compareJson(json_encode($lTestDb->getValue('childrenTestDb')->export($lStdPrivateInterfacer)),'[{"id":1,"parentTestDb":"[1,\"1501774389\"]"},{"id":2}]')) {
	throw new Exception('bad object : '.json_encode($lTestDb->getValue('childrenTestDb')->export($lStdPrivateInterfacer)));
}
if (!compareJson(json_encode($lTestDb->export($lStdPrivateInterfacer)), '{"id1":1,"id2":"1501774389","childrenTestDb":[1,2],"integer":2}')) {
	var_dump(json_encode($lPublicObjectJson));
	throw new Exception('bad object : '.json_encode($lTestDb->export($lStdPrivateInterfacer)));
}


// reset children
$lTestDb->initValue('childrenTestDb', false, false);
foreach (MainObjectCollection::getInstance()->getModelObjects('childTestDb') as $lChild) {
	MainObjectCollection::getInstance()->removeObject($lChild);
}

$lTestDb->getModel()->loadObject($lTestDb->getId(), null, true);
$lTestDb->reorderValues();

$time_end = microtime(true);
var_dump('request test exec time '.($time_end - $time_start));