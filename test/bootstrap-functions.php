<?php

use Comhon\Cache\CacheHandler;

require_once __DIR__ . '/../vendor/autoload.php';

function resetCache($configPath) {
	$config = json_decode(file_get_contents($configPath), true);
	$settings = $config['cache_settings'];
	$cacheHandler = CacheHandler::getInstance($settings, dirname($configPath));
	
	if (!$cacheHandler->reset()) {
		throw new \Exception('error when trying to reset cache');
	}
}

