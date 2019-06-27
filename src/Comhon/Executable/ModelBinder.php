<?php

use Comhon\Utils\Project\ModelBinder;
use Comhon\Utils\OptionManager;

require_once __DIR__ . '/../../../vendor/autoload.php';

$optionsDescription = [
	'config' => [
		'short' => 'c',
		'long' => 'config',
		'has_value' => true,
		'required' => true,
		'description' => 'path to config file',
	],
	'yes' => [
			'short' => 'y',
			'long' => 'yes',
			'has_value' => false,
			'description' => 'Automatic yes to prompts.',
			'long_description' => 'Automatic yes to prompts. Assume "yes" as answer to all prompts and run non-interactively.'
	]
];

$optionManager = new OptionManager();
$optionManager->registerOptionDesciption($optionsDescription);
if ($optionManager->hasHelpArgumentOption()) {
	echo $optionManager->getHelp();
	exit(0);
}

ModelBinder::exec($optionManager->getOption('config'), !$optionManager->hasOption('yes'));
