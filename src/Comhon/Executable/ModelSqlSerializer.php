<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Comhon\Utils\OptionManager;
use Comhon\Utils\Project\ModelSqlSerializer;

include __DIR__ . DIRECTORY_SEPARATOR . 'Loader.php';

$optionsDescription = [
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
		'required' => true,
		'description' => 'Database id or database connection informations',
		'long_description' =>
		'Database informations that will be used for models without serialization.' . PHP_EOL .
		'Value must be a simple id of database or match with following patterns : ' . PHP_EOL .
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
		'enum' => ['camel', 'pascal', 'kebab', 'snake', 'iso'],
		'default' => 'snake',
		'description' => 'case of tables and columns names (default snake case)',
	]
];

$optionManager = new OptionManager();
$optionManager->registerOptionDesciption($optionsDescription);
if ($optionManager->hasHelpArgumentOption()) {
	echo $optionManager->getHelp();
	exit(0);
}

try {
	ModelSqlSerializer::exec(
		$optionManager->getOption('config'),
		$optionManager->getOption('database'),
		$optionManager->getOption('case'),
		$optionManager->getOption('model'),
		$optionManager->hasOption('recursive')
	);
} catch (\Exception $e) {
	echo "\033[0;31m{$e->getMessage()}\033[0m".PHP_EOL;
	echo "script exited".PHP_EOL;
	exit(1);
}
