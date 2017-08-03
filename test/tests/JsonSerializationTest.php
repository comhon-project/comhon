<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\File\XmlFile;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

$manModelJson = ModelManager::getInstance()->getInstanceModel('manBodyJson');
$testJson = $manModelJson->loadObject(156);

$testJson->setId(200);
if ($testJson->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testJson->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

try {
	$testJson->save(XmlFile::CREATE);
	$throw = true;
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('serialization souhld not be successfull ');
}

$testJson->setId(789456159);
if ($testJson->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

if ($testJson->save(XmlFile::CREATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testJson->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testJson->save(XmlFile::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
if ($testJson->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

if ($testJson->save(XmlFile::UPDATE) !== 0) {
	throw new \Exception('serialization souhld not be successfull');
}

$time_end = microtime(true);
var_dump('json serialization test exec time '.($time_end - $time_start));