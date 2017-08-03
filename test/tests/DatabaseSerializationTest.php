<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\SqlTable;
use Comhon\Database\DatabaseController;

$time_start = microtime(true);

$modelPerson = ModelManager::getInstance()->getInstanceModel('person');
$person = $modelPerson->getObjectInstance();

/** ************************* test if casted object is updated in database ************************** **/

if ($person->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$databaseId = ModelManager::getInstance()->getInstanceModel('person')->getSerialization()->getSettings()->getValue('database')->getId();
$dbHandler = DatabaseController::getInstanceWithDataBaseId($databaseId);
$statement = $dbHandler->executeSimpleQuery('select sex from person where id = '.$person->getId());
$result = $statement->fetchAll();
if ($result[0]['sex'] !== 'person') {
	throw new \Exception("bad inheritance key '{$result[0]['sex']}'");
}

$person->cast(ModelManager::getInstance()->getInstanceModel('man'));
if ($person->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
$statement = $dbHandler->executeSimpleQuery('select sex from person where id = '.$person->getId());
$result = $statement->fetchAll();
if ($result[0]['sex'] !== 'man') {
	throw new \Exception("bad inheritance key '{$result[0]['sex']}'");
}

if ($person->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$time_end = microtime(true);
var_dump('database serialization test exec time '.($time_end - $time_start));