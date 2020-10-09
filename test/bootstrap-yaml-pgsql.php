<?php

use Test\Comhon\Data;

require __DIR__ . '/bootstrap-functions.php';
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

chdir(__DIR__);

Data::$config = './config/config-yaml-pgsql.json';
// cache is desactivate for yaml to test without caching
// resetCache(Data::$config); 

$dataSourceName = 'pgsql:dbname=database;host=localhost';
$pdo = new \PDO($dataSourceName, 'root', 'root');
$pdo->exec(file_get_contents(__DIR__.'/data/database/database_pgsql.sql'));