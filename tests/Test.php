<?php

use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\singleton\InstanceModel;
use comhon\object\object\SqlTable;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelEnum;
set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/ObjectManagerLib/source/');

require_once 'Comhon.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'RequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ExtendedModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ExtendedValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'XmlSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ImportExportTest.php';


$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setIdValue('id', $i);
}
var_dump(microtime(true) - $start);

$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setUndefinedValue('id', $i);
}
var_dump(microtime(true) - $start);

$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setUndefinedValueplop('id', $i);
}
var_dump(microtime(true) - $start);
var_dump("+++++++++++++++++++++++");
$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setIdValue('id', $i, false);
}
var_dump(microtime(true) - $start);

$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setUndefinedValue('id', $i, false);
}
var_dump(microtime(true) - $start);

$lObj = new Object('mainTestDb');
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	$lObj->setUndefinedValueplop('id', $i, false);
}
var_dump(microtime(true) - $start);

// id value merge
// mandatory value for import
// update id update mainobjectcollection
// test complex request
// getId from via pulic 'from' method (id must be public ?) 
// property ids not overridable manifest (sure ?)
// replace composition by agregation
// manage foreign property with several id

// versionning get instance model 3eme parametre version