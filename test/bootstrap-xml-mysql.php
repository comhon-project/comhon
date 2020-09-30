<?php

use Test\Comhon\Data;

require __DIR__ . '/bootstrap-functions.php';
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

chdir(__DIR__);

Data::$config = './config/config-xml-mysql.json';
resetCache(Data::$config);

$dataSourceName = 'mysql:dbname=database;host=localhost';
$pdo = new \PDO($dataSourceName, 'root', 'root');
$pdo->exec(file_get_contents(__DIR__.'/data/database/database_mysql.sql'));