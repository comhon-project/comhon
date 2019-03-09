<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Model;
use Comhon\Model\ModelForeign;
use Comhon\Object\ComhonDateTime;
use Comhon\Interfacer\StdObjectInterfacer;

$time_start = microtime(true);

if (!ModelManager::getInstance()->hasInstanceModel('Comhon\Config')) {
	throw new \Exception('model not initialized');
}
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Comhon\Config')) {
	throw new \Exception('model must be loaded');
}

$testModel    = ModelManager::getInstance()->getInstanceModel('Test\Test');
$testModelTow = ModelManager::getInstance()->getInstanceModel('Test\Test');

/** ****************************** same test model instance ****************************** **/
if ($testModel !== $testModelTow) {
	throw new \Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'test' ****************************** **/
if ($testModel->getName() !== 'Test\Test') {
	throw new \Exception('model hasn\'t good name');
}
if (json_encode($testModel->getPropertiesNames()) !== '["name","stringValue","floatValue","booleanValue","indexValue","percentageValue","dateValue","objectValue","objectValues","objectContainer","foreignObjectValues","enumValue","enumIntArray","enumFloatArray","objectRefParent"]') {
	throw new \Exception("model {$testModel->getName()} hasn't good properties : ".json_encode($testModel->getPropertiesNames()));
}

/** ******************** test local model 'personLocal' load status ******************** **/
if (!ModelManager::getInstance()->hasInstanceModel('Test\Test\PersonLocal')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('Test\Test\PersonLocal')) {
	throw new \Exception('model must be not loaded');
}
/** ******************** load model 'personLocal' by calling getmodel() ******************** **/
$localPersonModel = $testModel->getProperty('objectContainer')->getModel()->getProperty('person')->getModel();

/** ******************** test local model 'personLocal' load status ******************** **/
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Test\PersonLocal')) {
	throw new \Exception('model must be loaded');
}
if (!$localPersonModel->isLoaded()) {
	throw new \Exception('model must be loaded');
}

/** ******************** test local model defined recursively in distant manifest ******************** **/
if ($localPersonModel->getProperty('anObjectWithIdAndMore')->getModel()->getName() !== 'Test\Test\PersonLocal\ObjectWithIdAndMore') {
	throw new \Exception('bad model name');
}
if (!ModelManager::getInstance()->hasInstanceModel('Test\Test\PersonLocal\Recursive')) {
	throw new \Exception('missing instance model');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('Test\Test\PersonLocal\Recursive')) {
	throw new \Exception('model should not be loaded');
}
if ($localPersonModel->getProperty('recursiveLocal')->getModel()->getName() !== 'Test\Test\PersonLocal\Recursive') {
	throw new \Exception('bad model name');
}
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Test\PersonLocal\Recursive')) {
	throw new \Exception('model should be loaded');
}
if (!ModelManager::getInstance()->hasInstanceModel('Test\Test\PersonLocal\Recursive\ObjectWithIdAndMore')) {
	throw new \Exception('missing instance model');
}
$recursiveLocalModel = $localPersonModel->getProperty('recursiveLocal')->getModel();
if ($recursiveLocalModel !== ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal\Recursive')) {
	throw new \Exception('bad instance model');
}
if ($recursiveLocalModel->getProperty('anotherObjectWithIdAndMore')->getModel()->getName() !== 'Test\Test\PersonLocal\Recursive\ObjectWithIdAndMore') {
	throw new \Exception('bad model name');
}

if (ModelManager::getInstance()->hasInstanceModel('Test\TestXml\PersonLocal')) {
	throw new \Exception('missing instance model');
}
if (ModelManager::getInstance()->hasInstanceModel('Test\TestXml\PersonLocal\Recursive')) {
	throw new \Exception('missing instance model');
}
$recursiveLocalXmlModel = ModelManager::getInstance()->getInstanceModel('Test\TestXml\PersonLocal\Recursive');

if ($recursiveLocalXmlModel === $recursiveLocalModel) {
	throw new \Exception('should be different instances');
}

/** ****************************** same model instance ****************************** **/
if ($localPersonModel !== ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal')) {
	throw new \Exception('models haven\'t same instance');
}

/** ****************************** basic test for model 'personLocal' ****************************** **/
if ($localPersonModel->getName() !== 'Test\Test\PersonLocal') {
	throw new \Exception('model hasn\'t good name');
}
if (!compareJson(json_encode($localPersonModel->getPropertiesNames()), '["id","firstName","lastName","birthDate","birthPlace","bestFriend","father","mother","children","homes","anObjectWithIdAndMore","aForeignObjectWithIdAndMore","recursiveLocal"]')) {
	throw new \Exception("model {$localPersonModel->getName()} hasn't good properties : ".json_encode($localPersonModel->getPropertiesNames()));
}

/** ****************************** test load status of model 'place' ****************************** **/

if (!ModelManager::getInstance()->hasInstanceModel('Test\Place')) {
	throw new \Exception('model \'place\' not initialized');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('place')) {
	throw new \Exception('model must be not loaded');
}

$placeForeignModel = $localPersonModel->getProperty('birthPlace')->getModel();

if (!($placeForeignModel instanceof ModelForeign)) {
	throw new \Exception('model of property \'birthPlace\' is not a foreign model');
}
$placeModel = $placeForeignModel->getModel();
if (!$placeModel->isMain()) {
	throw new \Exception('foreign model of property \'birthPlace\' is not a main model');
}


if (!ModelManager::getInstance()->hasInstanceModel('Test\Place')) {
	throw new \Exception('model \'place\' not initialized');
}
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place')) {
	throw new \Exception('model must be loaded');
}

$placeModelTow = ModelManager::getInstance()->getInstanceModel('Test\Place');

/** ****************************** same place model instance ****************************** **/
if ($placeModel !== $placeModelTow) {
	throw new \Exception('models haven\'t same instance');
}


/** ****************************** basic test for model 'Test\TestDb' ****************************** **/

$testDbModel = ModelManager::getInstance()->getInstanceModel('Test\TestDb');

if ($testDbModel->getName() !== 'Test\TestDb') {
	throw new \Exception('model hasn\'t good name');
}
if (json_encode($testDbModel->getPropertiesNames()) !== '["id1","id2","date","timestamp","object","objectWithId","string","integer","mainParentTestDb","objectsWithId","foreignObjects","lonelyForeignObject","lonelyForeignObjectTwo","defaultValue","manBodyJson","womanXml","notSerializedValue","notSerializedForeignObject","boolean","boolean2","childrenTestDb","notLinkableArrayTestDb","notLinkableTestDb","notLinkableTestObjValue"]') {
	throw new \Exception("model {$testDbModel->getName()} hasn't good properties : ".json_encode($testDbModel->getPropertiesNames()));
}
var_dump(get_class($testDbModel));
$dbModel = $testDbModel->getSerialization()->getSettings()->getModel()->getProperty('database')->getModel();
if ($dbModel->getModel()->getName() !== 'Comhon\SqlDatabase') {
	throw new \Exception('model hasn\'t good name');
}
if ($testDbModel->getProperty('integer')->isPrivate()) {
	throw new \Exception('is private');
}
if (!$testDbModel->getProperty('string')->isPrivate()) {
	throw new \Exception('is not private');
}
if (!$testDbModel->getProperty('string')->isPrivate()) {
	throw new \Exception('is not private');
}
$localModel = ModelManager::getInstance()->getInstanceModel('Test\TestDb\ObjectWithIdAndMoreMore');
if (!$localModel->getProperty('plop3')->isPrivate()) {
	throw new \Exception('is not private');
}

if (!$testDbModel->getProperty('timestamp')->isSerializable()) {
	throw new \Exception('is not serializable');
}
if ($testDbModel->getProperty('notSerializedValue')->isSerializable()) {
	throw new \Exception('is serializable');
}
if ($testDbModel->getProperty('notSerializedForeignObject')->isSerializable()) {
	throw new \Exception('is serializable');
}

/** ****************************** test serialization before load ****************************** **/

$stdPrivateInterfacer = new StdObjectInterfacer();
$stdPrivateInterfacer->setPrivateContext(true);

if (
	json_encode($testDbModel->getSerialization()->getSettings()->export($stdPrivateInterfacer)) !== '{"name":"test","database":"1"}'
	&& json_encode($testDbModel->getSerialization()->getSettings()->export($stdPrivateInterfacer)) !== '{"name":"public.test","database":"2"}'
) {
	throw new \Exception("model {$testDbModel->getName()} hasn't good values");
}

if (
	json_encode($testDbModel->getSerialization()->getSettings()->getValue('database')->export($stdPrivateInterfacer)) !== '{"id":"1","DBMS":"mysql","host":"localhost","name":"database","user":"root","password":"root"}'
	&& json_encode($testDbModel->getSerialization()->getSettings()->getValue('database')->export($stdPrivateInterfacer)) !== '{"id":"2","DBMS":"pgsql","host":"localhost","name":"database","user":"root","password":"root"}'
) {
	throw new \Exception("model {$testDbModel->getName()} hasn't good values : ".json_encode($testDbModel->getSerialization()->getSettings()->getValue('database')->export($stdPrivateInterfacer)));
}

// LOAD VALUE
$testDbModel->getSerialization()->getSettings()->loadValue('database');

/** ****************************** test serialization after load ****************************** **/
if (
	json_encode($testDbModel->getSerialization()->getSettings()->export($stdPrivateInterfacer)) !== '{"name":"test","database":"1"}'
	&& json_encode($testDbModel->getSerialization()->getSettings()->export($stdPrivateInterfacer)) !== '{"name":"public.test","database":"2"}'
) {
	throw new \Exception("model {$testDbModel->getName()} hasn't good values");
}
$stdPublicInterfacer = new StdObjectInterfacer();
$objDb = $testDbModel->getSerialization()->getSettings()->getValue('database')->export($stdPublicInterfacer);
if (
	(json_encode($objDb) !== '{"id":"1","DBMS":"mysql","host":"localhost","name":"database","user":"root"}')
	&& (json_encode($objDb) !== '{"id":"2","DBMS":"pgsql","host":"localhost","name":"database","user":"root"}')
) {
	throw new \Exception("model {$testDbModel->getName()} hasn't good values ".json_encode($objDb));
}
if (!$testDbModel->getSerialization()->getSettings()->getValue('database')->isLoaded()) {
	throw new \Exception('object must be loaded');
}

/** ****************************** test load status of model 'Comhon\SqlDatabase' ****************************** **/
if (!ModelManager::getInstance()->hasInstanceModel('Comhon\SqlDatabase')) {
	throw new \Exception('model \'sqlDatabase\' not initialized');
}
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Comhon\SqlDatabase')) {
	throw new \Exception('model must be loaded');
}

/** ****************************** same serialization object and model instance ****************************** **/
if ($placeModel->getSerialization()->getSettings()->getValue('database') !== $testDbModel->getSerialization()->getSettings()->getValue('database')) {
	throw new \Exception('models haven\'t same serialization');
}

if ($placeModel->getSerialization()->getSettings()->getModel() !== $testDbModel->getSerialization()->getSettings()->getModel()) {
	throw new \Exception('models haven\'t same instance');
}

if (ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase') !== $testDbModel->getSerialization()->getSettings()->getValue('database')->getModel()) {
	throw new \Exception('models haven\'t same instance');
}

if (ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable') !== $testDbModel->getSerialization()->getSettings()->getModel()) {
	throw new \Exception('models haven\'t same instance');
}

$obj        = $testModel->getObjectInstance();
$modelArray = $obj->getModel()->getProperty('objectValues')->getModel();
$objArray   = $modelArray->getObjectInstance();
$objValue   = $obj->getModel()->getproperty('objectValue')->getModel()->getObjectInstance();

$obj->setId('sddsdfffff');
$obj->setValue('objectValue', $objValue);
$obj->setValue('objectValues', $objArray);
$obj->setValue('foreignObjectValues', $objArray);

if (!ModelManager::getInstance()->hasInstanceModel('Comhon\SqlTable')) {
	throw new \Exception('model already initialized');
}

/** **************** test Comhon DateTime ****************** **/

$dateTime = new ComhonDateTime('now');
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->add(new DateInterval('P0Y0M0DT5H0M0S'));
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->modify('+1 day');
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->setDate(2001, 2, 3);
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->setISODate(2008, 2);
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->setTime(14, 55);
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->setTimestamp(1171502725);
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$dateTime->sub(new DateInterval('P10D'));
if (!$dateTime->isUpdated()) {
	throw new \Exception('should be updated');
}
$dateTime->resetUpdatedStatus();
if ($dateTime->isUpdated()) {
	throw new \Exception('should not be updated');
}

$time_end = microtime(true);
var_dump('model test exec time '.($time_end - $time_start));

