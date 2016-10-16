<?php

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\ComplexLoadRequest;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\SimpleLoadRequest;
use objectManagerLib\object\MainObjectCollection;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\ModelForeign;

use objectManagerLib\controller\CompositionLoader;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\visitor\ObjectCollectionCreator;

$time_start = microtime(true);

$lTestModel    = InstanceModel::getInstance()->getInstanceModel('test');
$lTestModelTow = InstanceModel::getInstance()->getInstanceModel('test');

/** ****************************** same test model instance ****************************** **/
if ($lTestModel !== $lTestModelTow) {
	throw new Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'test' ****************************** **/
if ($lTestModel->getModelName() !== 'test') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lTestModel->getPropertiesNames()) !== '["name","objectValue","objectValues","objectContainer","foreignObjectValues"]') {
	throw new Exception("model {$lTestModel->getModelName()} hasn't good properties");
}

/** ******************** test local model 'personLocal' load status ******************** **/
if (!InstanceModel::getInstance()->hasInstanceModel('personLocal', 'test')) {
	throw new Exception('model not initialized');
}
if (InstanceModel::getInstance()->isModelLoaded('personLocal', 'test')) {
	throw new Exception('model must be not loaded');
}
/** ******************** load model 'personLocal' by calling getmodel() ******************** **/
$lLocalPersonModel = $lTestModel->getProperty('objectContainer')->getModel()->getProperty('person')->getModel();

/** ******************** test local model 'personLocal' load status ******************** **/
if (!InstanceModel::getInstance()->isModelLoaded('personLocal', 'test')) {
	throw new Exception('model must be loaded');
}
if (!$lLocalPersonModel->isLoaded()) {
	throw new Exception('model must be loaded');
}

/** ****************************** same model instance ****************************** **/
if ($lLocalPersonModel !== InstanceModel::getInstance()->getInstanceModel('personLocal', 'test')) {
	throw new Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'personLocal' ****************************** **/
if ($lLocalPersonModel->getModelName() !== 'personLocal') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lLocalPersonModel->getPropertiesNames()) !== '["id","firstName","lastName","age","birthPlace","sex","father","mother","children","homes"]') {
	throw new Exception("model {$lLocalPersonModel->getModelName()} hasn't good properties : ".json_encode($lLocalPersonModel->getPropertiesNames()));
}

/** ****************************** test load status of model 'place' ****************************** **/

if (!InstanceModel::getInstance()->hasInstanceModel('place')) {
	throw new Exception('model \'place\' not initialized');
}
if (InstanceModel::getInstance()->isModelLoaded('place')) {
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


if (!InstanceModel::getInstance()->hasInstanceModel('place')) {
	throw new Exception('model \'place\' not initialized');
}
if (!InstanceModel::getInstance()->isModelLoaded('place')) {
	throw new Exception('model must be loaded');
}

$lPlaceModelTow = InstanceModel::getInstance()->getInstanceModel('place');

/** ****************************** same place model instance ****************************** **/
if ($lPlaceModel !== $lPlaceModelTow) {
	throw new Exception('models haven\'t same instance');
}


/** ****************************** basic test for model 'testDb' ****************************** **/

/*
 if (InstanceModel::getInstance()->hasInstanceModel('sqlDatabase')) {
throw new Exception("model must be not initialized");
}
if (InstanceModel::getInstance()->isModelLoaded('sqlDatabase')) {
throw new Exception("model must be not loaded");
}
*/

$lTestDbModel = InstanceModel::getInstance()->getInstanceModel('testDb');

if ($lTestDbModel->getModelName() !== 'testDb') {
	throw new Exception('model hasn\'t good name');
}
if (json_encode($lTestDbModel->getPropertiesNames()) !== '["id1","id2","date","timestamp","object","string","integer","mainParentTestDb"]') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good properties : ".json_encode($lTestDbModel->getPropertiesNames()));
}
$lDbModel = $lTestDbModel->getSerialization()->getModel()->getPropertyModel('database');
if ($lDbModel->getModelName() !== 'sqlDatabase') {
	throw new Exception('model hasn\'t good name');
}

/** ****************************** test serialization before load ****************************** **/
if (json_encode($lTestDbModel->getSerialization()->toObject()) !== '{"name":"test","database":"1"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
if (json_encode($lTestDbModel->getSerialization()->getValue('database')->toObject()) !== '{"id":"1"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
if ($lTestDbModel->getSerialization()->getValue('database')->isLoaded()) {
	throw new Exception('object must be not loaded');
}

// LOAD VALUE
$lTestDbModel->getSerialization()->loadValue('database');

/** ****************************** test serialization after load ****************************** **/
if (json_encode($lTestDbModel->getSerialization()->toObject()) !== '{"name":"test","database":1}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
if (json_encode($lTestDbModel->getSerialization()->getValue('database')->toObject()) !== '{"id":1,"DBMS":"mysql","host":"localhost","name":"database","user":"root","password":"hcqzSM 92"}') {
	throw new Exception("model {$lTestDbModel->getModelName()} hasn't good values");
}
if (!$lTestDbModel->getSerialization()->getValue('database')->isLoaded()) {
	throw new Exception('object must be loaded');
}

/** ****************************** test load status of model 'sqlDatabase' ****************************** **/
if (!InstanceModel::getInstance()->hasInstanceModel('sqlDatabase')) {
	throw new Exception('model \'sqlDatabase\' not initialized');
}
if (!InstanceModel::getInstance()->isModelLoaded('sqlDatabase')) {
	throw new Exception('model must be loaded');
}

/** ****************************** same serialization object and model instance ****************************** **/
if ($lPlaceModel->getSerialization()->getValue('database') !== $lTestDbModel->getSerialization()->getValue('database')) {
	throw new Exception('models haven\'t same serialization');
}

if ($lPlaceModel->getSerialization()->getModel() !== $lTestDbModel->getSerialization()->getModel()) {
	throw new Exception('models haven\'t same instance');
}

if (InstanceModel::getInstance()->getInstanceModel('sqlDatabase') !== $lTestDbModel->getSerialization()->getValue('database')->getModel()) {
	throw new Exception('models haven\'t same instance');
}

if (InstanceModel::getInstance()->getInstanceModel('sqlTable') !== $lTestDbModel->getSerialization()->getModel()) {
	throw new Exception('models haven\'t same instance');
}

$lObj        = $lTestModel->getObjectInstance();
$lModelArray = new ModelArray($lTestModel, 'sesreer');
$lObjArray   = $lModelArray->getObjectInstance();
$lObjValue   = $lObj->getproperty('objectValue')->getModel()->getObjectInstance();

$lObj->setValue('name', 'sddsdfffff');
$lObj->setValue('objectValue', $lObjValue);
$lObj->setValue('objectValues', $lObjArray);
$lObj->setValue('foreignObjectValues', $lObjArray);

$time_end = microtime(true);
var_dump('model test exec time '.($time_end - $time_start));

