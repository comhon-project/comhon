<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

if (ModelManager::getInstance()->hasInstanceModel('Test\Body')) {
	throw new \Exception('model already initialized');
}
if (ModelManager::getInstance()->hasInstanceModel('Test\Body\Woman')) {
	throw new \Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('Test\Person')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('Test\Person')) {
	throw new \Exception('model already loaded');
}
$personModel = ModelManager::getInstance()->getInstanceModel('Test\Person');

if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Person')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('Test\Person\Woman')) {
	throw new \Exception('model already initialized');
}

$womanModel = ModelManager::getInstance()->getInstanceModel('Test\Person\Woman');

if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Person\Woman')) {
	throw new \Exception('model not initialized');
}
if (json_encode(array_keys($womanModel->getProperties())) !== '["id","firstName","lastName","birthDate","birthPlace","bestFriend","father","mother","children","homes","bodies"]') {
	throw new \Exception('bad model properties');
}
if ($womanModel->getSerializationSettings() !== $personModel->getSerializationSettings()) {
	throw new \Exception('not same serialization');
}
if (ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->getSerializationSettings() !== $personModel->getSerializationSettings()) {
	throw new \Exception('not same serialization');
}

if ($womanModel->getProperty('id') !== $personModel->getProperty('id')) {
	throw new \Exception('not same instance of property');
}

if (ModelManager::getInstance()->hasInstanceModel('Test\Body')) {
	throw new \Exception('model already initialized');
}
if (!ModelManager::getInstance()->hasInstanceModel('Test\Body\Woman')) {
	throw new \Exception('model not initialized');
}
if (ModelManager::getInstance()->hasInstanceModelLoaded('Test\Body\Woman')) {
	throw new \Exception('model already loaded');
}
$womanBodyModel = $womanModel->getProperty('bodies')->getModel()->getModel()->getModel();
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Body\Woman')) {
	throw new \Exception('model not loaded');
}
if ($womanBodyModel->getName() !== 'Test\Body\Woman') {
	throw new \Exception('bad model name');
}
if (!$womanBodyModel->isLoaded()) {
	throw new \Exception('model not loaded');
}
if (!ModelManager::getInstance()->hasInstanceModel('Test\Body')) {
	throw new \Exception('model not initialized');
}
if (!ModelManager::getInstance()->hasInstanceModelLoaded('Test\Body')) {
	throw new \Exception('model not loaded');
}
if (json_encode(array_keys($womanBodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner","chestSize"]') {
	throw new \Exception('bad model properties '.json_encode(array_keys($womanBodyModel->getProperties())));
}
$bodyModel = ModelManager::getInstance()->getInstanceModel('Test\Body');
if (json_encode(array_keys($bodyModel->getProperties())) !== '["id","date","height","weight","hairColor","hairCut","eyesColor","physicalAppearance","tatoos","piercings","arts","owner"]') {
	throw new \Exception('bad model properties');
}
if ($bodyModel->getProperty('hairColor') !== $womanBodyModel->getProperty('hairColor')) {
	throw new \Exception('not same instance of property');
}

$tatooModel = $womanBodyModel->getProperty('tatoos')->getModel()->getModel();
if ($tatooModel->getName() !== 'Test\Body\Tatoo') {
	throw new \Exception('bad model name');
}
if (json_encode(array_keys($tatooModel->getProperties())) !== '["type","location","tatooArtist"]') {
	throw new \Exception('bad model properties');
}
$artModel = $tatooModel->getParent();
$artModelTow = ModelManager::getInstance()->getInstanceModel('Test\Body\Art');

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

$bodyTatooModel = ModelManager::getInstance()->getInstanceModel('Test\Body\Tatoo');

if ($bodyTatooModel->getName() !== 'Test\Body\Tatoo') {
	throw new \Exception('bad model');
}

$throw = false;
try {
	ModelManager::getInstance()->getInstanceModel('Test\Body\Woman\Tatouage');
	$throw = true;
} catch (ComhonException $e) {
}
if ($throw) {
	throw new \Exception('get instance model with local model \'tatouage\' should fail');
}


$time_end = microtime(true);
var_dump('extended model test exec time '.($time_end - $time_start));