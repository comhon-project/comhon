<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Model;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

if (ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new \Exception('model already initialized');
}
if (ModelManager::getInstance()->hasInstanceModel('womanBody')) {
	throw new \Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('person')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('person')) {
	throw new \Exception('model already loaded');
}
$personModel = ModelManager::getInstance()->getInstanceModel('person');

if (!ModelManager::getInstance()->isModelLoaded('person')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('woman')) {
	throw new \Exception('model already initialized');
}

$womanModel = ModelManager::getInstance()->getInstanceModel('woman');

if (!ModelManager::getInstance()->isModelLoaded('woman')) {
	throw new \Exception('model not initialized');
}
if (json_encode(array_keys($womanModel->getProperties())) !== '["id","firstName","lastName","birthDate","birthPlace","bestFriend","father","mother","children","homes","bodies"]') {
	throw new \Exception('bad model properties');
}
if ($womanModel->getSerializationSettings() !== $personModel->getSerializationSettings()) {
	throw new \Exception('not same serialization');
}
if (ModelManager::getInstance()->getInstanceModel('man')->getSerializationSettings() !== $personModel->getSerializationSettings()) {
	throw new \Exception('not same serialization');
}

if ($womanModel->getProperty('id') !== $personModel->getProperty('id')) {
	throw new \Exception('not same instance of property');
}

if (ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new \Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('womanBody')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('womanBody')) {
	throw new \Exception('model already loaded');
}
$womanBodyModel = $womanModel->getProperty('bodies')->getModel()->getModel()->getModel();
if (!ModelManager::getInstance()->isModelLoaded('womanBody')) {
	throw new \Exception('model not loaded');
}
if ($womanBodyModel->getName() !== 'womanBody') {
	throw new \Exception('bad model name');
}
if (!$womanBodyModel->isLoaded()) {
	throw new \Exception('model not loaded');
}
if (!ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new \Exception('model not initialized');
}
if (!ModelManager::getInstance()->isModelLoaded('body')) {
	throw new \Exception('model not loaded');
}
if (json_encode(array_keys($womanBodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner","chestSize"]') {
	throw new \Exception('bad model properties '.json_encode(array_keys($womanBodyModel->getProperties())));
}
$bodyModel = ModelManager::getInstance()->getInstanceModel('body');
if (json_encode(array_keys($bodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner"]') {
	throw new \Exception('bad model properties');
}
if ($bodyModel->getProperty('hairColor') !== $womanBodyModel->getProperty('hairColor')) {
	throw new \Exception('not same instance of property');
}
if ($bodyModel->getProperty('owner') === $womanBodyModel->getProperty('owner')) {
	throw new \Exception('same instance of property');
}

$tatooModel = $womanBodyModel->getProperty('tatoos')->getModel()->getModel();
if ($tatooModel->getName() !== 'body\\tatoo') {
	throw new \Exception('bad model name');
}
if (json_encode(array_keys($tatooModel->getProperties())) !== '["type","location","tatooArtist"]') {
	throw new \Exception('bad model properties');
}
$artModel = $tatooModel->getParent();
$artModelTow = ModelManager::getInstance()->getInstanceModel('body\art');

if ($artModel !== $artModelTow) {
	throw new \Exception('not same instance of model');
}
if (json_encode(array_keys($artModel->getProperties())) !== '["type","location"]') {
	throw new \Exception('bad model properties');
}
if ($tatooModel->getProperty('location') !== $artModel->getProperty('location')) {
	throw new \Exception('not same instance of property');
}

/** ************** test types defined in extended model ****************** **/

$bodyTatooModel = ModelManager::getInstance()->getInstanceModel('body\tatoo');

if ($bodyTatooModel->getName() !== 'body\tatoo') {
	throw new \Exception('bad model');
}

$throw = false;
try {
	ModelManager::getInstance()->getInstanceModel('womanBody\tatouage');
	$throw = true;
} catch (ComhonException $e) {
}
if ($throw) {
	throw new \Exception('get instance model with local model \'tatouage\' should fail');
}


$time_end = microtime(true);
var_dump('extended model test exec time '.($time_end - $time_start));