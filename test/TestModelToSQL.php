<?php

use Comhon\Utils\InitProject\ModelToSQL;
use Comhon\Object\Config\Config;

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/comhon/src/');

require_once 'Comhon.php';

$output = __DIR__ . '/output';
$config = "./config/config.json";

try {
	ModelToSQL::exec($output, $config);
} catch(\Exception $e) {
	trigger_error($e->getMessage());
	trigger_error($e->getTraceAsString());
}