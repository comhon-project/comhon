<?php

use Comhon\Utils\InitProject\ModelToSQL;
use Comhon\Object\Config\Config;

$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__);

$output = __DIR__ . '/output';
$config = "./config/config-xml-mysql.json";

try {
	ModelToSQL::exec($output, $config);
} catch(\Exception $e) {
	trigger_error($e->getMessage());
	trigger_error($e->getTraceAsString());
}