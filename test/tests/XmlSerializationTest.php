<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\File\XmlFile;
use Comhon\Exception\SerializationException;

$time_start = microtime(true);

$testXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$testXml = $testXmlModel->loadObject('plop2');

$testXml->setId('plop4');
if ($testXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull 1');
}
if ($testXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull 2');
}

try {
	$testXml->save(XmlFile::CREATE);
	$throw = true;
} catch (SerializationException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('serialization souhld not be successfull 3');
}

$testXml->setId('non_existing_id');
if ($testXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull 4');
}

if ($testXml->save(XmlFile::CREATE) !== 1) {
	throw new \Exception('serialization souhld be successfull 5');
}
if ($testXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull 6');
}
if ($testXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull 7');
}
if ($testXml->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull 8');
}

if ($testXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull 9');
}

$time_end = microtime(true);
var_dump('xml serialization test exec time '.($time_end - $time_start));