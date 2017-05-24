<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\File\XmlFile;

$time_start = microtime(true);

$testXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$testXml = $testXmlModel->loadObject('plop2');

$testXml->setId('plop4');
if ($testXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

try {
	$testXml->save(XmlFile::CREATE);
	$throw = true;
} catch (Exception $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('serialization souhld not be successfull ');
}

$testXml->setId('non_existing_id');
if ($testXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

if ($testXml->save(XmlFile::CREATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testXml->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if ($testXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

$time_end = microtime(true);
var_dump('xml serialization test exec time '.($time_end - $time_start));