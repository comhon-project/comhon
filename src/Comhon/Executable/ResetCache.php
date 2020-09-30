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
use Comhon\Object\Config\Config;
use Comhon\Utils\Cache;

include __DIR__ . DIRECTORY_SEPARATOR . 'Loader.php';

$optionsDescription = [
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
	Config::setLoadPath($optionManager->getOption('config'));
	Cache::reset();
} catch (\Exception $e) {
	echo "\033[0;31m{$e->getMessage()}\033[0m".PHP_EOL;
	echo "script exited".PHP_EOL;
	exit(1);
}
