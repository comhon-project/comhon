<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as Object;
use Comhon\Object\Object as FinalObject;
use Comhon\Api\ObjectService;
use Comhon\Serialization\SqlTable;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);


/** ****************************** test request objects ****************************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"integer", "type":"ASC"}],
	"properties" : ["date","timestamp","integer","string"],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
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

$result = ObjectService::getObjects(json_decode($Json), true);

if (!is_object($result) || !isset($result->success) || !$result->success || !isset($result->result) || !is_array($result->result)) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

if (!compareJson(json_encode($result->result), '[{"date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"id1":1,"id2":"23"},{"date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":2,"id1":1,"id2":"101"}]')) {
	throw new \Exception('bad objects');
}

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"integer", "type":"ASC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
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

$result = ObjectService::getObjects(json_decode($Json), true);

if (!is_object($result) || !isset($result->success) || !$result->success || !isset($result->result) || !is_array($result->result)) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

if (!compareJson(json_encode($result->result), '[{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"object":null,"objectWithId":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean":false,"boolean2":true},{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":2,"object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean":false,"boolean2":true}]')) {
	throw new \Exception('bad objects : '.json_encode($result->result));
}

MainObjectCollection::getInstance()->getObject('[1,"23"]', 'testDb')->reorderValues();
MainObjectCollection::getInstance()->getObject('[1,"101"]', 'testDb')->reorderValues();

/** ****************************** test following export import objects ****************************** **/

$basedObjects  = [
	json_decode('{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"object":null,"objectWithId":null,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}'),
	json_decode('{"defaultValue":"default","id1":1,"id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')
];

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);
$stdPublicInterfacer = new StdObjectInterfacer();
$stdPublicInterfacer->setPrivateContext(false);

$xmlSerialInterfacer = new XMLInterfacer();
$xmlSerialInterfacer->setPrivateContext(false);
$xmlSerialInterfacer->setSerialContext(true);

$object = null;
foreach ($result->result as $index => $stdObject) {
	$object = new FinalObject('testDb');
	try {
		$object->fill($stdObject, $stdPrivateInterfacer);
		$throw = true;
	} catch (ComhonException $e) {
		$throw = false;
	}
	if ($throw) {
		throw new \Exception('import should works other instance already exists');
	}
	$id1 = $stdObject->id1;
	$id2 = $stdObject->id2;
	unset($stdObject->id1);
	unset($stdObject->id2);
	
	$object = new FinalObject('testDb');
	$object->fill($stdObject, $stdPrivateInterfacer);

	$object2 = new FinalObject('testDb');
	$object2->fill($object->export($xmlSerialInterfacer), $xmlSerialInterfacer);
	$object2->setValue('id1', $id1);
	$object2->setValue('id2', $id2);
	
	if (!compareJson(json_encode($object2->export($stdPrivateInterfacer)), json_encode($basedObjects[$index]))) {
		throw new \Exception('bad object');
	}
}

/** *************** test DateTime/DateTimeZone and unserializable value with database serialization ****************** **/

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');

/** @var Object $object */
$object = $dbTestModel->loadObject('[1,"1501774389"]');
$objectJson = $object->export($stdPrivateInterfacer);

$publicObjectJson = $object->export($stdPublicInterfacer);

if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($object->isUpdatedValue('timestamp')) {
	throw new \Exception('should not be updated');
}
if ($object->getValue('timestamp')->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($object->save(SqlTable::UPDATE) !== 0) {
	throw new \Exception('serialization should return 0 because there is no update');
}
// update dateTime
$object->getValue('timestamp')->sub(new DateInterval('P0Y0M0DT5H0M0S'));
if (!$object->isUpdated()) {
	throw new \Exception('should be updated');
}
if (!$object->isUpdatedValue('timestamp')) {
	throw new \Exception('should be updated');
}
if (!$object->getValue('timestamp')->isUpdated()) {
	throw new \Exception('should be updated');
}

if ($object->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be updated');
}
if ($object->isUpdatedValue('timestamp')) {
	throw new \Exception('should not be updated');
}
if ($object->getValue('timestamp')->isUpdated()) {
	throw new \Exception('should not be updated');
}

foreach ($object->getValues() as $name => $value) {
	$object->flagValueAsUpdated($name);
}

$updateResultByDBSM = [
	'mysql' => 0,
	'pgsql' => 1,
];
$DBSM = $object->getModel()->getSerializationSettings()->getValue('database')->getValue('DBMS');

if ($object->save(SqlTable::UPDATE) !== $updateResultByDBSM[$DBSM]) {
	throw new \Exception('serialization should return 0 because there is no update IN database');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$object = $dbTestModel->loadObject('[1,"1501774389"]', null, true);
$object->getValue('timestamp')->add(new DateInterval('P0Y0M0DT5H0M0S'));
$object->setValue('notSerializedValue', 'azezaeaze');
$object->setValue('notSerializedForeignObject', $object->getValue('lonelyForeignObject'));
foreach ($object->getValues() as $name => $value) {
	$object->flagValueAsUpdated($name);
}
if ($object->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$object->unsetValue('notSerializedValue');
$object->unsetValue('notSerializedForeignObject');
$object->resetUpdatedStatus();

$object = $dbTestModel->loadObject('[1,"1501774389"]', [], true);

if (!compareJson(json_encode($object->export($stdPrivateInterfacer)), json_encode($objectJson))) {
	throw new \Exception('bad object');
}

/** ************************* test deleted values with database serialization ************************ **/

$value = $object->getValue('integer');
$object->unsetValue('integer');

$object->save(SqlTable::UPDATE);
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$object = $dbTestModel->loadObject('[1,"1501774389"]', null, true);
if (!$object->hasValue('integer') || !is_null($object->getValue('integer'))) {
	throw new \Exception('should not have integer value');
}
$object->setValue('integer', $value);
$object->reorderValues();

$object->save(SqlTable::UPDATE);
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

/** ****************************** test simple load request api ****************************** **/

/** ************* new object with filter ************** **/

MainObjectCollection::getInstance()->removeObject(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'));

$params = new stdClass();
$params->model = 'testDb';
$params->id = '[1,"1501774389"]';
$params->properties = ['date','timestamp','integer','string'];
$result = ObjectService::getObject($params, true);

if (!is_object($result) || !isset($result->success) || !$result->success) {
	throw new \Exception('simple load request failed : '.json_encode($result));
}
if (!compareJson(json_encode($result->result), '{"date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2,"id1":1,"id2":"1501774389"}')) {
	throw new \Exception('bad object : '.json_encode($result->result));
}

/** ************* existing object partial object ************** **/

$params = new stdClass();
$params->model = 'testDb';
$params->id = '[1,"1501774389"]';
$result = ObjectService::getObject($params);

if (!is_object($result) || !isset($result->success) || !$result->success) {
	throw new \Exception('simple load request failed'.json_encode($result));
}
if (!compareJson(json_encode($result->result), '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2}')) {
	throw new \Exception('bad object : '.json_encode($result->result));
}

/** ************* new object full object ************** **/

MainObjectCollection::getInstance()->removeObject(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'));
$params = new stdClass();
$params->model = 'testDb';
$params->id = '[1,"1501774389"]';
$result = ObjectService::getObject($params);

if (!is_object($result) || !isset($result->success) || !$result->success) {
	throw new \Exception('simple load request failed');
}
if (!compareJson(json_encode($result->result), '{"defaultValue":"default","id1":1,"id2":"1501774389","date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[{"plop":"1","plop2":"heyplop2","plop4":"heyplop4","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","__inheritance__":"testDb\\\\objectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"foreignObjects":[{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMoreMore"},{"id":"1","__inheritance__":"testDb\\\\objectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"}],"lonelyForeignObject":{"id":"11","__inheritance__":"testDb\\\\objectWithIdAndMore"},"lonelyForeignObjectTwo":"11","manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}')) {
	throw new \Exception('bad object : '.json_encode($result->result));
}

/** ************* existing full object with filter ************** **/

$params = new stdClass();
$params->model = 'testDb';
$params->id = '[1,"1501774389"]';
$params->properties = ['date','timestamp','integer','string'];
$result = ObjectService::getObject($params, true);

if (!is_object($result) || !isset($result->success) || !$result->success) {
	throw new \Exception('simple load request failed');
}
if (!compareJson(json_encode($result->result), '{"date":"2016-04-12T05:14:33+02:00","timestamp":"2016-10-13T11:50:19+02:00","integer":2,"id1":1,"id2":"1501774389"}')) {
	throw new \Exception('bad object : '.json_encode($result->result));
}

/** ************* existing full object reodered ************** **/

MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb')->reorderValues();
$params = new stdClass();
$params->model = 'testDb';
$params->id = '[1,"1501774389"]';
$result = ObjectService::getObject($params);

if (!is_object($result) || !isset($result->success) || !$result->success) {
	throw new \Exception('simple load request failed');
}
if (!compareJson(json_encode($result->result), json_encode($publicObjectJson))) {
	throw new \Exception('bad object : '.json_encode($result->result));
}

$testDb = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$testDb->loadValue('childrenTestDb', ['id']);

if (!compareJson(json_encode($testDb->getValue('childrenTestDb')->export($stdPrivateInterfacer)), '[{"id":1,"parentTestDb":"[1,\"1501774389\"]"},{"id":2,"parentTestDb":"[1,\"1501774389\"]"}]')) {
	throw new \Exception('bad object : '.json_encode($testDb->getValue('childrenTestDb')->export($stdPrivateInterfacer)));
}
foreach ($testDb->getValue('childrenTestDb') as $child) {
	if ($testDb !== $child->getValue('parentTestDb')) {
		throw new \Exception('should be same instance');
	}
}

$children = $testDb->getValue('childrenTestDb');
$testDb1 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$testDb->reset();
$testDb2 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');
$testDb->setIsLoaded(false);
$testDb->setId('[1,"1501774389"]', false);
$testDb3 = MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb');

if (!is_null($testDb2)) {
	throw new \Exception('should be null');
}
if (is_null($testDb3)) {
	throw new \Exception('should be not null');
}
if ($testDb !== $testDb1 || $testDb1 !== $testDb3) {
	throw new \Exception('should be same instance');
}
$testDb->unsetValue('id2');
if (!is_null(MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb'))) {
	throw new \Exception('should be null');
}
$testDb->setValue('id2', '1501774389');
if ($testDb !== MainObjectCollection::getInstance()->getObject('[1,"1501774389"]', 'testDb')) {
	throw new \Exception('should be same instance');
}

$testDb->setValue('childrenTestDb', $children, false);
$testDb->getValue('childrenTestDb')->getValue(0)->setValue('parentTestDb', $testDb);
$testDb->getValue('childrenTestDb')->getValue(0)->loadValue('parentTestDb', ['integer']);
$testDb->getValue('childrenTestDb')->getValue(1)->unsetValue('parentTestDb');

if (!compareJson(json_encode($testDb->getValue('childrenTestDb')->export($stdPrivateInterfacer)),'[{"id":1,"parentTestDb":"[1,\"1501774389\"]"},{"id":2}]')) {
	throw new \Exception('bad object : '.json_encode($testDb->getValue('childrenTestDb')->export($stdPrivateInterfacer)));
}
if (!compareJson(json_encode($testDb->export($stdPrivateInterfacer)), '{"defaultValue":"default","id1":1,"id2":"1501774389","childrenTestDb":[1,2],"integer":2}')) {
	throw new \Exception('bad object : '.json_encode($testDb->export($stdPrivateInterfacer)));
}


// reset children
$testDb->initValue('childrenTestDb', false, false);
foreach (MainObjectCollection::getInstance()->getModelObjects('childTestDb') as $child) {
	MainObjectCollection::getInstance()->removeObject($child);
}

$testDb->getModel()->loadObject($testDb->getId(), null, true);
$testDb->reorderValues();

$time_end = microtime(true);
var_dump('request test exec time '.($time_end - $time_start));