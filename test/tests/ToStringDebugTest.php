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

$expected = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'object_var_dump.txt');

ob_start();
var_dump($testDb);
$actual = ob_get_clean();
$actualCleaned = preg_replace('/\#.+\(/', '# (', $actual);

if ($actualCleaned !== $expected) {
	throw new \Exception('bad value var_dump()'.PHP_EOL.$actual);
}
if ($testDb->__toString() !== file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'object_to_string.json')) {
	throw new \Exception('bad value __toString()');
}

$time_end = microtime(true);
var_dump('toString test exec time '.($time_end - $time_start));