<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\SqlTable;
use Comhon\Database\DatabaseHandler;

$time_start = microtime(true);

$modelPerson = ModelManager::getInstance()->getInstanceModel('Test\Person\Woman');
$woman = $modelPerson->getObjectInstance();

$throw = false;
try {
	$woman->save();
	$throw = true;
} catch (Exception $e) {
	if ($e->getCode() !== 805) {
		throw new \Exception("wrong exception code, {$e->getCode()} given, 805 expected");
	}
}
if ($throw) {
	throw new \Exception('serialization souhld be stopped due to not null value');
}

/** ************************* test if casted object is updated in database ************************** **/

$woman->setValue('firstName', 'jane');
if ($woman->save() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$databaseId = ModelManager::getInstance()->getInstanceModel('Test\Person')->getSqlTableSettings()->getValue('database')->getId();
$dbHandler = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
$statement = $dbHandler->execute('select sex from person where id = '.$woman->getId());
$result = $statement->fetchAll();
if ($result[0]['sex'] !== 'Test\Person\Woman') {
	throw new \Exception("bad inheritance key '{$result[0]['sex']}'");
}

$woman->cast(ModelManager::getInstance()->getInstanceModel('Test\Person\Woman\WomanExtended'));
if ($woman->save(SqlTable::UPDATE) !== 1) {
	throw new \Exception('serialization souhld be successfull');
}
$statement = $dbHandler->execute('select sex from person where id = '.$woman->getId());
$result = $statement->fetchAll();

if ($result[0]['sex'] !== 'Test\Person\Woman\WomanExtended') {
	throw new \Exception("bad inheritance key '{$result[0]['sex']}'");
}

if ($woman->delete() !== 1) {
	throw new \Exception('serialization souhld be successfull');
}

$statement = $dbHandler->execute('select sex from person where id = '.$woman->getId());
$result = $statement->fetchAll();

if (!empty($result)) {
	throw new \Exception("not deleted");
}

$time_end = microtime(true);
var_dump('database serialization test exec time '.($time_end - $time_start));