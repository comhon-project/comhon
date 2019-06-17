<?php

use Test\Comhon\Data;

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

chdir(__DIR__);

Data::$config = './config/config-json-pgsql.json';