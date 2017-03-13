<?php

use comhon\model\singleton\ModelManager;
use comhon\serialization\file\XmlFile;

$time_start = microtime(true);

$lManModelJson = ModelManager::getInstance()->getInstanceModel('manBodyJson');
$lTestJson = $lManModelJson->loadObject(156);

$lTestJson->setId(200);
if ($lTestJson->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestJson->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

try {
	$lTestJson->save(XmlFile::CREATE);
	$lThrow = true;
} catch (Exception $e) {
	$lThrow = false;
}
if ($lThrow) {
	throw new \Exception('serialization souhld not be successfull ');
}

$lTestJson->setId(789456159);
if ($lTestJson->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

if ($lTestJson->save(XmlFile::CREATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestJson->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestJson->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($lTestJson->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if ($lTestJson->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

$time_end = microtime(true);
var_dump('json serialization test exec time '.($time_end - $time_start));