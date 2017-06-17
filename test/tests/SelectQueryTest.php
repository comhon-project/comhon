<?php

use Comhon\Database\SelectQuery;
use Comhon\Database\TableNode;
use Comhon\Database\OnLiteral;
use Comhon\Database\Literal;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Database\DatabaseController;

$time_start = microtime(true);

$personTable = new TableNode('person');
$childTable = new TableNode('person');
$childTable->addSelectedColumn('id', 'child_id');
$selectQuery = new SelectQuery($personTable);
$selectQuery->join(SelectQuery::INNER_JOIN, $childTable, new OnLiteral($personTable, 'id', Literal::EQUAL, $childTable, 'father_id'));
$selectQuery->where(new Literal($childTable, 'first_name', Literal::EQUAL, ['john', 'Jean']));
$databaseModel = ModelManager::getInstance()->getInstanceModel('sqlDatabase');
$database = $databaseModel->loadObject('1');
$databaseController = DatabaseController::getInstanceWithDataBaseObject($database);

$selectQuery->setFocusOnMainTable();
$selectQuery->addOrder('id');
$selectQuery->setTableFocus($childTable);
$selectQuery->addGroup('id');

$throw = true;
try {
	$row = $databaseController->executeSelectQuery($selectQuery);
} catch (Exception $e) {
	$throw = false;
}
if ($throw) {
	throw new Exception('expression should be thrown due to duplicated table names');
}

$childTable->setAlias('child');

list($query, $params) = $selectQuery->export();

if ($query !== 'SELECT person.*,child.id AS child_id FROM  person inner join person AS child on person.id = child.father_id  WHERE child.first_name  IN  (?,?) GROUP BY child.id ORDER BY person.id') {
	throw new Exception('bad query');
}
if ($params !== ['john', 'Jean']) {
	throw new Exception('bad params');
}

$row = $databaseController->executeSelectQuery($selectQuery);

if (!compareJson(json_encode($row), '[{"id":"1","first_name":"Bernard","lastName":"Dupond","sex":"man","birth_place":"2","father_id":null,"mother_id":null,"birth_date":"2016-11-13 19:04:05","best_friend":null,"child_id":"5"},{"id":"1","first_name":"Bernard","lastName":"Dupond","sex":"man","birth_place":"2","father_id":null,"mother_id":null,"birth_date":"2016-11-13 19:04:05","best_friend":null,"child_id":"6"}]')) {
	throw new Exception('bad result');
}

$time_end = microtime(true);
var_dump('select query test exec time '.($time_end - $time_start));

