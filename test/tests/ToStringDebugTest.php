<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonDateTime;

$time_start = microtime(true);

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lTestDb = $lDbTestModel->getObjectInstance();

$lTestDb->setValue('id1', 789201, false);
$lTestDb->setValue('timestamp', new ComhonDateTime('now'), false);
$lTestDb->unsetValue('childrenTestDb', false);
$lTestDb->unsetValue('defaultValue', false);

var_dump($lTestDb);
echo $lTestDb;

$time_end = microtime(true);
var_dump('toString test exec time '.($time_end - $time_start));