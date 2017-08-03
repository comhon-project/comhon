<?php

use Comhon\Database\SelectQuery;
use Comhon\Database\TableNode;
use Comhon\Database\OnLiteral;
use Comhon\Logic\Literal;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Database\DatabaseController;
use Comhon\Database\SimpleDbLiteral;
use Comhon\Exception\ComhonException;

$time_start = microtime(true);

$personTable = new TableNode('person');
$childTable = new TableNode('person');
$childTable->addSelectedColumn('id', 'child_id');
$selectQuery = new SelectQuery($personTable);
$selectQuery->join(SelectQuery::INNER_JOIN, $childTable, new OnLiteral($personTable, 'id', Literal::EQUAL, $childTable, 'father_id'));
$selectQuery->where(new SimpleDbLiteral($childTable, 'first_name', Literal::EQUAL, ['john', 'Jean']));
$database  = ModelManager::getInstance()->getInstanceModel('person')->getSerialization()->getSettings()->getValue('database');
$databaseController = DatabaseController::getInstanceWithDataBaseObject($database);

$selectQuery->setFocusOnMainTable();
$selectQuery->addOrder('id');
$selectQuery->setTableFocus($childTable);
$selectQuery->addGroup('id');

$throw = true;
try {
	$row = $databaseController->executeSelectQuery($selectQuery);
} catch (ComhonException $e) {
	$throw = false;
}
if ($throw) {
	throw new \Exception('expression should be thrown due to duplicated table names');
}

$childTable->setAlias('child');

list($query, $params) = $selectQuery->export();

if ($query !== 'SELECT person.*,child.id AS child_id FROM  person inner join person AS child on person.id = child.father_id  WHERE child.first_name  IN  (?,?) GROUP BY child.id ORDER BY person.id') {
	throw new \Exception('bad query');
}
if ($params !== ['john', 'Jean']) {
	throw new \Exception('bad params');
}

// change query due to postgresql that doesn't manage retrieved columns not in group
$personTable->resetSelectedColumns();
$personTable->selectAllColumns(false);
$selectQuery->resetOrderColumns();
$selectQuery->addOrder('id');
list($query, $params) = $selectQuery->export();

$row = $databaseController->executeSelectQuery($selectQuery);

if (
	(json_encode($row) !== '[{"child_id":5},{"child_id":6}]')
	&& (json_encode($row) !== '[{"child_id":"5"},{"child_id":"6"}]')
) {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('select query test exec time '.($time_end - $time_start));

