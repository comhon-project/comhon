<?php

use comhon\model\singleton\ModelManager;
use comhon\object\serialization\file\XmlFile;

$time_start = microtime(true);

$lTestXmlModel = ModelManager::getInstance()->getInstanceModel('testXml');
$lTestXml = $lTestXmlModel->loadObject('plop2');

$lTestXml->setId('plop4');
if ($lTestXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

try {
	$lTestXml->save(XmlFile::CREATE);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('serialization souhld not be successfull ');
}

$lTestXml->setId('non_existing_id');
if ($lTestXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

if ($lTestXml->save(XmlFile::CREATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestXml->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestXml->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestXml->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if ($lTestXml->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

$time_end = microtime(true);
var_dump('xml serialization test exec time '.($time_end - $time_start));