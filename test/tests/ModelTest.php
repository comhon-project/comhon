<?php

use comhon\object\singleton\ModelManager;
use comhon\object\ComplexLoadRequest;
use comhon\object\object\Object;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;
use comhon\object\model\ModelArray;
use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\model\ModelForeign;

use comhon\controller\AggregationLoader;
use comhon\controller\ForeignObjectLoader;
use comhon\visitor\ObjectCollectionCreator;

$time_start = microtime(true);

if (!ModelManager::getInstance()->hasInstanceModel('config')) {
	throw new Exception('model not initialized');
}
if (!ModelManager::getInstance()->isModelLoaded('config')) {
	throw new Exception('model must be loaded');
}
if (ModelManager::getInstance()->hasInstanceModel('sqlTable')) {
	throw new Exception('model already initialized');
}

$lTestModel    = ModelManager::getInstance()->getInstanceModel('test');
$lTestModelTow = ModelManager::getInstance()->getInstanceModel('test');

/** ****************************** same test model instance ****************************** **/
if ($lTestModel !== $lTestModelTow) {
	throw new Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'test' ****************************** **/
if ($lTestModel->getModelName() !== 'test') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lTestModel->getPropertiesNames()) !== '["name","stringValue","floatValue","booleanValue","dateValue","objectValue","objectValues","objectContainer","foreignObjectValues","enumValue","enumIntArray","enumFloatArray","objectRefParent"]') {
	throw new Exception("model {$lTestModel->getModelName()} hasn't good properties : ".json_encode($lTestModel->getPropertiesNames()));
}

/** ******************** test local model 'personLocal' load status ******************** **/
if (!ModelManager::getInstance()->hasInstanceModel('personLocal', 'test')) {
	throw new Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('personLocal', 'test')) {
	throw new Exception('model must be not loaded');
}
/** ******************** load model 'personLocal' by calling getmodel() ******************** **/
$lLocalPersonModel = $lTestModel->getProperty('objectContainer')->getModel()->getProperty('person')->getModel();

/** ******************** test local model 'personLocal' load status ******************** **/
if (!ModelManager::getInstance()->isModelLoaded('personLocal', 'test')) {
	throw new Exception('model must be loaded');
}
if (!$lLocalPersonModel->isLoaded()) {
	throw new Exception('model must be loaded');
}

/** ****************************** same model instance ****************************** **/
if ($lLocalPersonModel !== ModelManager::getInstance()->getInstanceModel('personLocal', 'test')) {
	throw new Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'personLocal' ****************************** **/
if ($lLocalPersonModel->getModelName() !== 'personLocal') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lLocalPersonModel->getPropertiesNames()) !== '["id","firstName","lastName","birthDate","birthPlace","bestFriend","father","mother","children","homes"]') {
	throw new Exception("model {$lLocalPersonModel->getModelName()} hasn't good properties : ".json_encode($lLocalPersonModel->getPropertiesNames()));
}

/** ****************************** test load status of model 'place' ****************************** **/

if (!ModelManager::getInstance()->hasInstanceModel('place')) {
	throw new Exception('model \'place\' not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('place')) {
	throw new Exception('model must be not loaded');
}

$lPlaceForeignModel = $lLocalPersonModel->getProperty('birthPlace')->getModel();

if (!($lPlaceForeignModel instanceof ModelForeign)) {
	throw new Exception('model of property \'birthPlace\' is not a foreign model');
}
$lPlaceModel = $lPlaceForeignModel->getModel();
if (!($lPlaceModel instanceof MainModel)) {
	throw new Exception('foreign model of property \'birthPlace\' is not a main model');
}


if (!ModelManager::getInstance()->hasInstanceModel('place')) {
	throw new Exception('model \'place\' not initialized');
}
if (!ModelManager::getInstance()->isModelLoaded('place')) {
	throw new Exception('model must be loaded');
}

$lPlaceModelTow = ModelManager::getInstance()->getInstanceModel('place');

/** ****************************** same place model instance ****************************** **/
if ($lPlaceModel !== $lPlaceModelTow) {
	throw new Exception('models haven\'t same instance');
}


/** ****************************** basic test for model 'testDb' ****************************** **/

/*
 if (ModelManager::getInstance()->hasInstanceModel('sqlDatabase')) {
throw new Exception("model must be not initialized");
}
if (ModelManager::getInstance()->isModelLoaded('sqlDatabase')) {
throw new Exception("model must be not loaded");
}
*/

$lTestDbModel = ModelManager::getInstance()->getInstanceModel('testDb');

if ($lTestDbModel->getModelName() !== 'testDb') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lTestDbModel->getPropertiesNames()) !== '["id1","id2","date","timestamp","object","objectWithId","string","integer","mainParentTestDb","objectsWithId","foreignObjects","lonelyForeignObject","lonelyForeignObjectTwo","defaultValue","manBodyJson","womanXml","notSerializedValue","notSerializedForeignObject","boolean","boolean2","childrenTestDb"]') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good properties : ".json_encode($lTestDbModel->getPropertiesNames()));
}
$lDbModel = $lTestDbModel->getSerialization()->getProperty('database')->getModel();
if ($lDbModel->getModelName() !== 'sqlDatabase') {
	throw new Exception('model hasn\'t good name');
}
if ($lTestDbModel->getProperty('integer')->isPrivate()) {
	throw new Exception('is private');
}
if (!$lTestDbModel->getProperty('string')->isPrivate()) {
	throw new Exception('is not private');
}
if (!$lTestDbModel->getProperty('string')->isPrivate()) {
	throw new Exception('is not private');
}
$lLocalModel = ModelManager::getInstance()->getInstanceModel('objectWithIdAndMoreMore', 'testDb');
if (!$lLocalModel->getProperty('plop3')->isPrivate()) {
	throw new Exception('is not private');
}

if (!$lTestDbModel->getProperty('timestamp')->isSerializable()) {
	throw new Exception('is not serializable');
}
if ($lTestDbModel->getProperty('notSerializedValue')->isSerializable()) {
	throw new Exception('is serializable');
}
if ($lTestDbModel->getProperty('notSerializedForeignObject')->isSerializable()) {
	throw new Exception('is serializable');
}

/** ****************************** test serialization before load ****************************** **/
if (json_encode($lTestDbModel->getSerialization()->toPrivateStdObject()) !== '{"name":"test","database":"1"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}

if (json_encode($lTestDbModel->getSerialization()->getValue('database')->toPrivateStdObject()) !== '{"id":"1"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values : ".json_encode($lTestDbModel->getSerialization()->getValue('database')->toPrivateStdObject()));
}
if ($lTestDbModel->getSerialization()->getValue('database')->isLoaded()) {
	throw new Exception('object must be not loaded');
}

// LOAD VALUE
$lTestDbModel->getSerialization()->loadValue('database');

/** ****************************** test serialization after load ****************************** **/
if (json_encode($lTestDbModel->getSerialization()->toPrivateStdObject()) !== '{"name":"test","database":"1"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
$lObjDb = $lTestDbModel->getSerialization()->getValue('database')->toPrivateStdObject();
unset($lObjDb->password);
if (json_encode($lObjDb) !== '{"id":"1","DBMS":"mysql","host":"localhost","name":"database","user":"root"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
if (!$lTestDbModel->getSerialization()->getValue('database')->isLoaded()) {
	throw new Exception('object must be loaded');
}

/** ****************************** test load status of model 'sqlDatabase' ****************************** **/
if (!ModelManager::getInstance()->hasInstanceModel('sqlDatabase')) {
	throw new Exception('model \'sqlDatabase\' not initialized');
}
if (!ModelManager::getInstance()->isModelLoaded('sqlDatabase')) {
	throw new Exception('model must be loaded');
}

/** ****************************** same serialization object and model instance ****************************** **/
if ($lPlaceModel->getSerialization()->getValue('database') !== $lTestDbModel->getSerialization()->getValue('database')) {
	throw new Exception('models haven\'t same serialization');
}

if ($lPlaceModel->getSerialization()->getModel() !== $lTestDbModel->getSerialization()->getModel()) {
	throw new Exception('models haven\'t same instance');
}

if (ModelManager::getInstance()->getInstanceModel('sqlDatabase') !== $lTestDbModel->getSerialization()->getValue('database')->getModel()) {
	throw new Exception('models haven\'t same instance');
}

if (ModelManager::getInstance()->getInstanceModel('sqlTable') !== $lTestDbModel->getSerialization()->getModel()) {
	throw new Exception('models haven\'t same instance');
}

$lObj        = $lTestModel->getObjectInstance();
$lModelArray = new ModelArray($lTestModel, 'sesreer');
$lObjArray   = $lModelArray->getObjectInstance();
$lObjValue   = $lObj->getproperty('objectValue')->getModel()->getObjectInstance();

$lObj->setId('sddsdfffff');
$lObj->setValue('objectValue', $lObjValue);
$lObj->setValue('objectValues', $lObjArray);
$lObj->setValue('foreignObjectValues', $lObjArray);

if (!ModelManager::getInstance()->hasInstanceModel('sqlTable')) {
	throw new Exception('model already initialized');
}

$time_end = microtime(true);
var_dump('model test exec time '.($time_end - $time_start));

