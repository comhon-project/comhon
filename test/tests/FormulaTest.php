<?php

use Comhon\Logic\Literal;
use Comhon\Logic\Clause;
use Comhon\Database\SimpleDbLiteral;

$time_start = microtime(true);

$clause = new Clause(Clause::CONJUNCTION);
$clause->addLiteral(new SimpleDbLiteral('table', 'first_name', Literal::EQUAL, 'Jean'));
$clause->addElement(new SimpleDbLiteral('table', 'first_name', Literal::EQUAL, 'John'));


$subClause = new Clause(Clause::DISJUNCTION);
$subClause->addElement(new Clause(Clause::CONJUNCTION));
$clause->addClause($subClause);
$subClause2 = new Clause(Clause::DISJUNCTION);
$clause->addElement($subClause2);
$subClause2->addLiteral(new SimpleDbLiteral('table', 'first_name', Literal::DIFF, 'Jean'));

if ($clause->exportDebug()!== '(table.first_name = Jean and table.first_name = John and (table.first_name <> Jean))') {
	throw new \Exception('bad export');
}
$values = [];
if ($clause->export($values)!== '(table.first_name = ? and table.first_name = ? and (table.first_name <> ?))') {
	throw new \Exception('bad export');
}
if ($values !== ["Jean","John","Jean"]) {
	throw new \Exception('bad values');
}

$time_end = microtime(true);
var_dump('clause test exec time '.($time_end - $time_start));

