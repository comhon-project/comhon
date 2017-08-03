<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject as Object;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Database\DatabaseController;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

$time_start = microtime(true);

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);

$stdSerialInterfacer = new StdObjectInterfacer();
$stdSerialInterfacer->setPrivateContext(true);
$stdSerialInterfacer->setSerialContext(true);

$xmlPrivateInterfacer = new XMLInterfacer();
$xmlPrivateInterfacer->setPrivateContext(true);

$xmlSerialInterfacer = new XMLInterfacer();
$xmlSerialInterfacer->setPrivateContext(true);
$xmlSerialInterfacer->setSerialContext(true);

$flattenArrayPrivateInterfacer = new AssocArrayInterfacer();
$flattenArrayPrivateInterfacer->setPrivateContext(true);
$flattenArrayPrivateInterfacer->setFlattenValues(true);

$flattenArraySerialInterfacer = new AssocArrayInterfacer();
$flattenArraySerialInterfacer->setPrivateContext(true);
$flattenArraySerialInterfacer->setFlattenValues(true);
$flattenArraySerialInterfacer->setSerialContext(true);

$childDbTestModel = ModelManager::getInstance()->getInstanceModel('childTestDb');
$object = $childDbTestModel->getObjectInstance();
$object->setValue('id', 1);
$object->setValue('name', 'plop');
$property = $object->getProperty('parentTestDb', true);
if (!($property instanceof MultipleForeignProperty)) {
	throw new \Exception('bad property class : '.get_class($property));
}
$pattern = ["parent_id_1" => "id1", "parent_id_2" => "id2"];
foreach ($property->getMultipleIdProperties() as $serializationName => $idProperty) {
	if (!array_key_exists($serializationName, $pattern) || $pattern[$serializationName] !== $idProperty->getName()) {
		throw new \Exception('bad multiple id properties');
	}
}
$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$parentObject = $dbTestModel->loadObject('[1,"1501774389"]');
$object->setValue('parentTestDb', $parentObject);

/************************************************** export **********************************************/

if (json_encode($object->export($stdPrivateInterfacer)) !== '{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"}') {
	throw new \Exception('bad object value');
}
if (!compareXML($xmlPrivateInterfacer->toString($object->export($xmlPrivateInterfacer)), '<childTestDb id="1" name="plop"><parentTestDb>[1,"1501774389"]</parentTestDb></childTestDb>')) {
	throw new \Exception('bad object value');
}
if (json_encode($object->export($flattenArrayPrivateInterfacer)) !== '{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"}') {
	throw new \Exception('bad object value');
}

if (json_encode($object->export($stdSerialInterfacer)) !== '{"id":1,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new \Exception('bad object value');
}
if (!compareXML($xmlSerialInterfacer->toString($object->export($xmlSerialInterfacer)), '<childTestDb id="1" name="plop" parent_id_1="1" parent_id_2="1501774389"/>')) {
	throw new \Exception('bad object value');
}
if (json_encode($object->export($flattenArraySerialInterfacer)) !== '{"id":1,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new \Exception('bad object value');
}

/************************************************** import **********************************************/

$object = $childDbTestModel->import(json_decode('{"id":2,"name":"plop","parent_id_2":"1501774389","parent_id_1":1}'), $stdSerialInterfacer);
if (!compareJson(json_encode($object->export($stdSerialInterfacer)), '{"id":2,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}')) {
	throw new \Exception('bad object value');
}
if ($object->getValue('parentTestDb') !== $parentObject) {
	throw new \Exception('bad foreign object instance');
}
$object = $childDbTestModel->import(simplexml_load_string('<childTestDb id="3" name="plop" parent_id_2="1501774389" parent_id_1="1"/>'), $xmlSerialInterfacer);
if (!compareXML($xmlSerialInterfacer->toString($object->export($xmlSerialInterfacer)), '<childTestDb id="3" name="plop" parent_id_1="1" parent_id_2="1501774389"/>')) {
	throw new \Exception('bad object value');
}
if ($object->getValue('parentTestDb') !== $parentObject) {
	throw new \Exception('bad foreign object instance');
}
$object = $childDbTestModel->import(json_decode('{"id":4,"name":"plop","parent_id_2":"1501774389","parent_id_1":1}', true), $flattenArraySerialInterfacer);
if (json_encode($object->export($flattenArraySerialInterfacer)) !== '{"id":4,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new \Exception('bad object value');
}
if ($object->getValue('parentTestDb') !== $parentObject) {
	throw new \Exception('bad foreign object instance');
}

/******************************************** load aggregation ******************************************/

$parentObject->initValue('childrenTestDb', false, false);
$parentObject->loadValue('childrenTestDb');

if (json_encode($parentObject->getValue('childrenTestDb')->export($stdPrivateInterfacer)) !== '[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]') {
	throw new \Exception('bad foreign object instance');
}

/********************************************** test save *******************************************/

$databaseId  = ModelManager::getInstance()->getInstanceModel('person')->getSerialization()->getSettings()->getValue('database')->getId();
$dbHandler = DatabaseController::getInstanceWithDataBaseId($databaseId);
$object->unsetValue('id');

if (!is_null($object->getValue('id'))) {
	throw new \Exception('id must be unset');
}
$statement = $dbHandler->executeSimpleQuery('select count(*) from child_test');
$result = $statement->fetchAll();
$count = (integer) $result[0][0];
if ($count !== 2) {
	throw new \Exception('bad count '.$result[0][0]);
}

if ($object->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if (is_null($object->getValue('id'))) {
	throw new \Exception('id must be set');
}
$statement = $dbHandler->executeSimpleQuery('select count(*) from child_test');
$result = $statement->fetchAll();
$count = (integer) $result[0][0];
if ($count !== 3) {
	throw new \Exception('bad count');
}

if ($object->save() !== 0) {
	throw new \Exception('serialization souhld return 0 because there is no update');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$object->setValue('name', 'hehe');
if ($object->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$object->setValue('name', 'plop');
if ($object->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($object->isUpdated()) {
	throw new \Exception('should not be flaged as updated after save');
}
if (count($object->getUpdatedValues()) !== 0) {
	throw new \Exception('should not have updated values after save');
}

$statement = $dbHandler->executeSimpleQuery('select count(*) from child_test');
$result = $statement->fetchAll();
$count = (integer) $result[0][0];
if ($count !== 3) {
	throw new \Exception('bad count');
}

if ($object->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$statement = $dbHandler->executeSimpleQuery('select count(*) from child_test');
$result = $statement->fetchAll();
$count = (integer) $result[0][0];
if ($count !== 2) {
	throw new \Exception('bad count');
}


$time_end = microtime(true);
var_dump('multiple foreign test exec time '.($time_end - $time_start));