<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

use Comhon\Object\Config\Config;
use Comhon\Exception\ComhonException;
use Comhon\Cache\CacheHandler;
use Comhon\Cache\FileSystemCacheHandler;

class Cache {
	
	/**
	 * reset cache
	 * 
	 * @throws ComhonException throw exception if cache is not successfully reset
	 * @return boolean true if success, false if there is no cache configured in configuration file
	 */
	public static function reset() {
		$settings = Config::getInstance()->getCacheSettings();
		if (is_null($settings)) {
			return false;
		}
		$cacheHandler = CacheHandler::getInstance($settings);
		if ($cacheHandler instanceof FileSystemCacheHandler) {
			$cacheHandler->setDirectory(
				Config::getInstance()->transformPath($cacheHandler->getDirectory())
			);
		}
		if (!$cacheHandler->reset()) {
			throw new ComhonException('error when trying to reset cache');
		}
		return true;
	}
    
}
