<?php

use Test\Comhon\Data;

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

chdir(__DIR__);

Data::$config = './config/config-yaml-pgsql.json';

$dataSourceName = 'pgsql:dbname=database;host=localhost';
$pdo = new \PDO($dataSourceName, 'root', 'root');
$pdo->exec(file_get_contents(__DIR__.'/data/database/database_pgsql.sql'));