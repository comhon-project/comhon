<?php

use Comhon\Utils\Project\ModelToSQL;
use Comhon\Utils\OptionManager;

require_once __DIR__ . '/../../../vendor/autoload.php';

$optionsDescription = [
	'output' => [
		'short' => 'o',
		'long' => 'output',
		'has_value' => true,
		'required' => true,
		'description' => 'path to output folder',
	],
	'model' => [
		'short' => 'm',
		'long' => 'model',
		'has_value' => true,
		'description' => 'process only given model',
	],
	'recursive' => [
		'short' => 'r',
		'long' => 'recursive',
		'has_value' => false,
		'description' => 'if model is provided, process recursively models with same name space',
	],
	'config' => [
		'short' => 'c',
		'long' => 'config',
		'has_value' => true,
		'required' => true,
		'description' => 'path to config file',
	]
];

$optionManager = new OptionManager();
$optionManager->registerOptionDesciption($optionsDescription);
if ($optionManager->hasHelpArgumentOption()) {
	echo $optionManager->getHelp();
	exit(0);
}

try {
	ModelToSql::exec(
		$optionManager->getOption('config'),
		$optionManager->getOption('output'),
		$optionManager->getOption('model'),
		$optionManager->hasOption('recursive')
	);
} catch (\Exception $e) {
	echo "\033[0;31m{$e->getMessage()}\033[0m".PHP_EOL;
	echo "script exited".PHP_EOL;
	exit(1);
}
