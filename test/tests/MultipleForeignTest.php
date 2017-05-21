<?php

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\model\property\MultipleForeignProperty;
use comhon\database\DatabaseController;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\AssocArrayInterfacer;

$time_start = microtime(true);

$lStdPrivateInterfacer = new StdObjectInterfacer();
$lStdPrivateInterfacer->setPrivateContext(true);

$lStdSerialInterfacer = new StdObjectInterfacer();
$lStdSerialInterfacer->setPrivateContext(true);
$lStdSerialInterfacer->setSerialContext(true);

$lXmlPrivateInterfacer = new XMLInterfacer();
$lXmlPrivateInterfacer->setPrivateContext(true);

$lXmlSerialInterfacer = new XMLInterfacer();
$lXmlSerialInterfacer->setPrivateContext(true);
$lXmlSerialInterfacer->setSerialContext(true);

$lFlattenArrayPrivateInterfacer = new AssocArrayInterfacer();
$lFlattenArrayPrivateInterfacer->setPrivateContext(true);
$lFlattenArrayPrivateInterfacer->setFlattenValues(true);

$lFlattenArraySerialInterfacer = new AssocArrayInterfacer();
$lFlattenArraySerialInterfacer->setPrivateContext(true);
$lFlattenArraySerialInterfacer->setFlattenValues(true);
$lFlattenArraySerialInterfacer->setSerialContext(true);

$lChildDbTestModel = ModelManager::getInstance()->getInstanceModel('childTestDb');
$lObject = $lChildDbTestModel->getObjectInstance();
$lObject->setValue('id', 1);
$lObject->setValue('name', 'plop');
$lProperty = $lObject->getProperty('parentTestDb', true);
if (!($lProperty instanceof MultipleForeignProperty)) {
	throw new Exception('bad property class : '.get_class($lProperty));
}
$lPattern = ["parent_id_1" => "id1", "parent_id_2" => "id2"];
foreach ($lProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
	if (!array_key_exists($lSerializationName, $lPattern) || $lPattern[$lSerializationName] !== $lIdProperty->getName()) {
		throw new Exception('bad multiple id properties');
	}
}
$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lParentObject = $lDbTestModel->loadObject('[1,"1501774389"]');
$lObject->setValue('parentTestDb', $lParentObject);

/************************************************** export **********************************************/

if (json_encode($lObject->export($lStdPrivateInterfacer)) !== '{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"}') {
	throw new Exception('bad object value');
}
if (!compareXML($lXmlPrivateInterfacer->toString($lObject->export($lXmlPrivateInterfacer)), '<childTestDb id="1" name="plop"><parentTestDb>[1,"1501774389"]</parentTestDb></childTestDb>')) {
	throw new Exception('bad object value');
}
if (json_encode($lObject->export($lFlattenArrayPrivateInterfacer)) !== '{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"}') {
	throw new Exception('bad object value');
}

if (json_encode($lObject->export($lStdSerialInterfacer)) !== '{"id":1,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new Exception('bad object value');
}
if (!compareXML($lXmlSerialInterfacer->toString($lObject->export($lXmlSerialInterfacer)), '<childTestDb id="1" name="plop" parent_id_1="1" parent_id_2="1501774389"/>')) {
	throw new Exception('bad object value');
}
if (json_encode($lObject->export($lFlattenArraySerialInterfacer)) !== '{"id":1,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new Exception('bad object value');
}

/************************************************** import **********************************************/

$lObject = $lChildDbTestModel->import(json_decode('{"id":2,"name":"plop","parent_id_2":"1501774389","parent_id_1":1}'), $lStdSerialInterfacer);
if (!compareJson(json_encode($lObject->export($lStdSerialInterfacer)), '{"id":2,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}')) {
	throw new Exception('bad object value');
}
if ($lObject->getValue('parentTestDb') !== $lParentObject) {
	throw new Exception('bad foreign object instance');
}
$lObject = $lChildDbTestModel->import(simplexml_load_string('<childTestDb id="3" name="plop" parent_id_2="1501774389" parent_id_1="1"/>'), $lXmlSerialInterfacer);
if (!compareXML($lXmlSerialInterfacer->toString($lObject->export($lXmlSerialInterfacer)), '<childTestDb id="3" name="plop" parent_id_1="1" parent_id_2="1501774389"/>')) {
	throw new Exception('bad object value');
}
if ($lObject->getValue('parentTestDb') !== $lParentObject) {
	throw new Exception('bad foreign object instance');
}
$lObject = $lChildDbTestModel->import(json_decode('{"id":4,"name":"plop","parent_id_2":"1501774389","parent_id_1":1}', true), $lFlattenArraySerialInterfacer);
if (json_encode($lObject->export($lFlattenArraySerialInterfacer)) !== '{"id":4,"name":"plop","parent_id_1":1,"parent_id_2":"1501774389"}') {
	throw new Exception('bad object value');
}
if ($lObject->getValue('parentTestDb') !== $lParentObject) {
	throw new Exception('bad foreign object instance');
}

/******************************************** load aggregation ******************************************/

$lParentObject->initValue('childrenTestDb', false, false);
$lParentObject->loadValue('childrenTestDb');

if (json_encode($lParentObject->getValue('childrenTestDb')->export($lStdPrivateInterfacer)) !== '[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]') {
	throw new Exception('bad foreign object instance');
}

/********************************************** test save *******************************************/

$lDbHandler = DatabaseController::getInstanceWithDataBaseId(1);
$lObject->unsetValue('id');

if (!is_null($lObject->getValue('id'))) {
	throw new Exception('id must be unset');
}
$lStatement = $lDbHandler->executeSimpleQuery('select count(*) from child_test');
$lResult = $lStatement->fetchAll();
if ($lResult[0][0] !== '2') {
	throw new Exception('bad count');
}

if ($lObject->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if (is_null($lObject->getValue('id'))) {
	throw new Exception('id must be set');
}
$lStatement = $lDbHandler->executeSimpleQuery('select count(*) from child_test');
$lResult = $lStatement->fetchAll();
if ($lResult[0][0] !== '3') {
	throw new Exception('bad count');
}

if ($lObject->save() !== 0) {
	throw new \Exception('serialization souhld return 0 because there is no update');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lObject->setValue('name', 'hehe');
if ($lObject->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lObject->setValue('name', 'plop');
if ($lObject->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lObject->isUpdated()) {
	throw new Exception('should not be flaged as updated after save');
}
if (count($lObject->getUpdatedValues()) !== 0) {
	throw new Exception('should not have updated values after save');
}

$lStatement = $lDbHandler->executeSimpleQuery('select count(*) from child_test');
$lResult = $lStatement->fetchAll();
if ($lResult[0][0] !== '3') {
	throw new Exception('bad count');
}

if ($lObject->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$lStatement = $lDbHandler->executeSimpleQuery('select count(*) from child_test');
$lResult = $lStatement->fetchAll();
if ($lResult[0][0] !== '2') {
	throw new Exception('bad count');
}


$time_end = microtime(true);
var_dump('multiple foreign test exec time '.($time_end - $time_start));