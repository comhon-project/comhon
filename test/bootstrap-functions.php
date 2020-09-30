<?php

use Comhon\Cache\CacheHandler;
use Comhon\Cache\FileSystemCacheHandler;

require_once __DIR__ . '/../vendor/autoload.php';

function resetCache($configPath) {
	$config = json_decode(file_get_contents($configPath), true);
	$settings = $config['cache_settings'];
	$cacheHandler = CacheHandler::getInstance($settings);
	if ($cacheHandler instanceof FileSystemCacheHandler && substr($cacheHandler->getDirectory(), 0, 1) == '.') {
		$cacheHandler->setDirectory(
			__DIR__ . DIRECTORY_SEPARATOR . dirname($configPath) . DIRECTORY_SEPARATOR . $cacheHandler->getDirectory()
		);
	}
	if (!$cacheHandler->reset()) {
		throw new \Exception('error when trying to reset cache');
	}
}

