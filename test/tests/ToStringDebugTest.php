<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonDateTime;
use Comhon\Object\Config\Config;

$time_start = microtime(true);

$dbTestModel = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
$testDb = $dbTestModel->getObjectInstance();

$testDb->setValue('id1', 789201, false);
$testDb->setValue('timestamp', new ComhonDateTime('2000-01-01'), false);
$testDb->unsetValue('childrenTestDb', false);
$testDb->unsetValue('defaultValue', false);

if (Config::getInstance()->getManifestFormat() == 'json') {
	$first = '#1102';
	$second = '#1118';
} else {
	$first = '#945';
	$second = '#850';
}
$varDumpContent = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'object_var_dump.txt');
$varDumpContent = str_replace(['#_1', '#_2'], [$first, $second], $varDumpContent);

ob_start();
var_dump($testDb);
$var_dump_content = ob_get_clean();

if ($var_dump_content !== $varDumpContent) {
	throw new \Exception('bad value var_dump()'.PHP_EOL.$var_dump_content);
}
if ($testDb->__toString() !== file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'object_to_string.json')) {
	throw new \Exception('bad value __toString()');
}

$time_end = microtime(true);
var_dump('toString test exec time '.($time_end - $time_start));