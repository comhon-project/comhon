<?php

use comhon\model\singleton\ModelManager;
use comhon\model\Model;

$time_start = microtime(true);

if (ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new Exception('model already initialized');
}
if (ModelManager::getInstance()->hasInstanceModel('womanBody')) {
	throw new Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('person')) {
	throw new Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('person')) {
	throw new Exception('model already loaded');
}
$lPersonModel = ModelManager::getInstance()->getInstanceModel('person');

if (!ModelManager::getInstance()->isModelLoaded('person')) {
	throw new Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('woman')) {
	throw new Exception('model already initialized');
}

$lWomanModel = ModelManager::getInstance()->getInstanceModel('woman');

if (!ModelManager::getInstance()->isModelLoaded('woman')) {
	throw new Exception('model not initialized');
}
if (json_encode(array_keys($lWomanModel->getProperties())) !== '["id","firstName","lastName","birthDate","birthPlace","bestFriend","father","mother","children","homes","bodies"]') {
	throw new Exception('bad model properties');
}
if ($lWomanModel->getSerializationSettings() !== $lPersonModel->getSerializationSettings()) {
	throw new Exception('not same serialization');
}
if (ModelManager::getInstance()->getInstanceModel('man')->getSerializationSettings() !== $lPersonModel->getSerializationSettings()) {
	throw new Exception('not same serialization');
}

if ($lWomanModel->getProperty('id') !== $lPersonModel->getProperty('id')) {
	throw new Exception('not same instance of property');
}

if (ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('womanBody')) {
	throw new Exception('model not initialized');
}
if (ModelManager::getInstance()->isModelLoaded('womanBody')) {
	throw new Exception('model already loaded');
}
$lWomanBodyModel = $lWomanModel->getProperty('bodies')->getModel()->getModel()->getModel();
if (!ModelManager::getInstance()->isModelLoaded('womanBody')) {
	throw new Exception('model not loaded');
}
if ($lWomanBodyModel->getName() !== 'womanBody') {
	throw new Exception('bad model name');
}
if (!$lWomanBodyModel->isLoaded()) {
	throw new Exception('model not loaded');
}
if (!ModelManager::getInstance()->hasInstanceModel('body')) {
	throw new Exception('model not initialized');
}
if (!ModelManager::getInstance()->isModelLoaded('body')) {
	throw new Exception('model not loaded');
}
if (json_encode(array_keys($lWomanBodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner","chestSize"]') {
	throw new Exception('bad model properties '.json_encode(array_keys($lWomanBodyModel->getProperties())));
}
$lBodyModel = ModelManager::getInstance()->getInstanceModel('body');
if (json_encode(array_keys($lBodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner"]') {
	throw new Exception('bad model properties');
}
if ($lBodyModel->getProperty('hairColor') !== $lWomanBodyModel->getProperty('hairColor')) {
	throw new Exception('not same instance of property');
}
if ($lBodyModel->getProperty('owner') === $lWomanBodyModel->getProperty('owner')) {
	throw new Exception('same instance of property');
}

$lTatooModel = $lWomanBodyModel->getProperty('tatoos')->getModel()->getModel();
if ($lTatooModel->getName() !== 'tatoo') {
	throw new Exception('bad model name');
}
if (json_encode(array_keys($lTatooModel->getProperties())) !== '["type","location","tatooArtist"]') {
	throw new Exception('bad model properties');
}
$lArtModel = $lTatooModel->getExtendsModel();
$lArtModelTow = ModelManager::getInstance()->getInstanceModel('art', 'body');

if ($lArtModel !== $lArtModelTow) {
	throw new Exception('not same instance of model');
}
if (json_encode(array_keys($lArtModel->getProperties())) !== '["type","location"]') {
	throw new Exception('bad model properties');
}
if ($lTatooModel->getProperty('location') !== $lArtModel->getProperty('location')) {
	throw new Exception('not same instance of property');
}

/** ************** test types defined in extended model ****************** **/

ModelManager::getInstance()->getInstanceModel('tatoo', 'body');
ModelManager::getInstance()->getInstanceModel('tatoo', 'womanBody');

$throw = false;
try {
	ModelManager::getInstance()->getInstanceModel('tatouage', 'womanBody');
	$throw = true;
} catch (Exception $e) {
}
if ($throw) {
	throw new Exception('get instance model with local model \'tatouage\' should fail');
}


$time_end = microtime(true);
var_dump('extended model test exec time '.($time_end - $time_start));