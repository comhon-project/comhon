<?php

use comhon\model\singleton\ModelManager;
use comhon\object\ComhonDateTime;

$time_start = microtime(true);

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lTestDb = $lDbTestModel->getObjectInstance();

$lTestDb->setValue('id1', 789201, false);
$lTestDb->setValue('timestamp', new ComhonDateTime('now'), false);
$lTestDb->deleteValue('childrenTestDb', false);
$lTestDb->deleteValue('defaultValue', false);

var_dump($lTestDb);
echo $lTestDb;

$time_end = microtime(true);
var_dump('value test exec time '.($time_end - $time_start));