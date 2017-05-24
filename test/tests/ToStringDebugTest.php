<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonDateTime;

$time_start = microtime(true);

$dbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$testDb = $dbTestModel->getObjectInstance();

$testDb->setValue('id1', 789201, false);
$testDb->setValue('timestamp', new ComhonDateTime('now'), false);
$testDb->unsetValue('childrenTestDb', false);
$testDb->unsetValue('defaultValue', false);

var_dump($testDb);
echo $testDb;

$time_end = microtime(true);
var_dump('toString test exec time '.($time_end - $time_start));