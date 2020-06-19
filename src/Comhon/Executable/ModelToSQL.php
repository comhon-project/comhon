<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Comhon\Utils\Project\ModelToSQL;
use Comhon\Utils\OptionManager;

include __DIR__ . DIRECTORY_SEPARATOR . 'Loader.php';

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
	],
	'update' => [
		'short' => 'u',
		'long' => 'update',
		'has_value' => false,
		'description' => 'connect to database and build table update query if table already exist',
		'long_description' =>
			'connect to database and build table update query if model has been updated and table already exist.' . PHP_EOL .
			'operations managed : ' . PHP_EOL .
			' - add column if a new serializable property is defined in model' . PHP_EOL .
			' - drop column if a serializable property has been deleted from model' . PHP_EOL .
			'operations not managed : ' . PHP_EOL .
			' - update property with a different type,' . PHP_EOL .
			'   an error will be thrown' . PHP_EOL .
			' - update property serialization name,' . PHP_EOL .
			'   it will generate drop and add instructions' . PHP_EOL .
			' - update table name is not managed,' . PHP_EOL .
			'   it will generate a create table instruction'
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
		$optionManager->hasOption('update'),
		$optionManager->getOption('model'),
		$optionManager->hasOption('recursive')
	);
} catch (\Exception $e) {
	echo "\033[0;31m{$e->getMessage()}\033[0m".PHP_EOL;
	echo "script exited".PHP_EOL;
	exit(1);
}
