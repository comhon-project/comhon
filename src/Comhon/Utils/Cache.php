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

use Comhon\Exception\ComhonException;
use Comhon\Model\Singleton\ModelManager;

class Cache {
	
	/**
	 * reset cache
	 * 
	 * @throws ComhonException throw exception if cache is not successfully reset
	 * @return boolean true if success, false if there is no cache configured in configuration file
	 */
	public static function reset() {
		$cacheHandler = ModelManager::getInstance()->getCacheHandler();
		if (is_null($cacheHandler)) {
			return false;
		}
		if (!$cacheHandler->reset()) {
			throw new ComhonException('error when trying to reset cache');
		}
		return true;
	}
    
}
