<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\SqlTable;
use Comhon\Database\DatabaseController;

$time_start = microtime(true);

$lModelPerson = ModelManager::getInstance()->getInstanceModel('person');
$lPerson = $lModelPerson->getObjectInstance();

/** ************************* test if casted object is updated in database ************************** **/

if ($lPerson->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$lDbHandler = DatabaseController::getInstanceWithDataBaseId(1);
$lStatement = $lDbHandler->executeSimpleQuery('select sex from person where id = '.$lPerson->getId());
$lResult = $lStatement->fetchAll();
if ($lResult[0]['sex'] !== 'person') {
	throw new Exception("bad inheritance key '{$lResult[0]['sex']}'");
}

$lPerson->cast(ModelManager::getInstance()->getInstanceModel('man'));
if ($lPerson->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
$lStatement = $lDbHandler->executeSimpleQuery('select sex from person where id = '.$lPerson->getId());
$lResult = $lStatement->fetchAll();
if ($lResult[0]['sex'] !== 'man') {
	throw new Exception("bad inheritance key '{$lResult[0]['sex']}'");
}

if ($lPerson->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$time_end = microtime(true);
var_dump('database serialization test exec time '.($time_end - $time_start));