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
	'input' => [
		'short' => 'i',
		'long' => 'input',
		'has_value' => true,
		'description' => 'path to a folder to filter manifest to process',
	],
	'config' => [
		'short' => 'x',
		'long' => 'config',
		'has_value' => true,
		'required' => true,
		'description' => 'path to config file',
	],
	'database' => [
		'short' => 'd',
		'long' => 'database',
		'has_value' => true,
		'description' => 'Database informations',
		'pattern' => '^([^:]+:){5,6}[^:]+$',
		'long_description' =>
		'Database informations that will be used for models without serialization.' . PHP_EOL .
		'Value must match with following patterns : ' . PHP_EOL .
		'id:DBMS:host:name:user:password or id:DBMS:host:name:user:password:port' . PHP_EOL .
		' - id is your database identifier that will be used in Comhon framework' . PHP_EOL .
		' - DBMS is your database management system' . PHP_EOL .
		' - host is your database host' . PHP_EOL .
		' - name is your database name' . PHP_EOL .
		' - user is your database user name' . PHP_EOL .
		' - password is your database password' . PHP_EOL .
		' - port is your database port (optional)'
	],
	'case' => [
		'short' => 'c',
		'long' => 'case',
		'has_value' => true,
		'enum' => ['camel', 'pascal', 'kebab', 'snake'],
		'description' => 'column name\'s case',
	]
];

$optionManager = new OptionManager();
$optionManager->registerOptionDesciption($optionsDescription);
if ($optionManager->hasHelpArgumentOption()) {
	echo $optionManager->getHelp();
	exit(0);
}

ModelToSQL::exec(
	$optionManager->getOption('output'),
	$optionManager->getOption('config'),
	$optionManager->getOption('case'),
	$optionManager->getOption('database'),
	$optionManager->getOption('input')
);
