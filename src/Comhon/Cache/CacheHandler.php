<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Cache;

use Comhon\Object\Config\Config;
use Comhon\Exception\Cache\CacheException;

/**
 * CacheHandler permit to cache comhon models to load them more rapidly.
 * It permit to register comhon configuration object too.
 * This class and inherited class should not be instanciated manually, 
 * it is implicitly used in ModelManager
 */
abstract class CacheHandler {
	
	/** @var string */
	const DIRECTORY = 'directory';
	
	/** @var string */
	const MEMCACHED = 'memcached';
	
	/**
	 * 
	 * @param string $settings contains the informations required to instanciate a cache handler.
	 *                         it must begin with the handler name followed by a colon, 
	 *                         followed by specific handler informations.
	 *                         for example : "directory:/my/path/to/cache/directory"
	 * @param string $config_ad the path to config directory (may be usefull if settings have relative path)
	 */
	public static function getInstance(string $settings, $config_ad = null) {
		$pos = strpos($settings, ':');
		if ($pos === false) {
			throw new CacheException("invalid cache handler settings : '$settings' (no colon)");
		}
		$name = substr($settings, 0, $pos);
		$specificSettings = substr($settings, $pos + 1);
		
		switch ($name) {
			case self::DIRECTORY:
				if ($specificSettings[0] == '.') {
					if (is_null($config_ad)) {
						throw new CacheException("settings path is relative but config directory is not provided");
					}
					$specificSettings = $config_ad . DIRECTORY_SEPARATOR . $specificSettings;
				}
				return new FileSystemCacheHandler($specificSettings);
				break;
			case self::MEMCACHED:
				return new MemCachedHandler($specificSettings);
				break;
			default:
				throw new CacheException("invalid cache handler name : '$name'");
		}
	}
	
	/**
	 * verify if there is a cached value under given key.
	 * 
	 * @return boolean
	 */
	abstract public function hasValue(string $key);
	
	/**
	 * get cached value according given key.
	 * 
	 * @return string|null
	 */
	abstract public function getValue(string $key);
	
	/**
	 * register value into cache according given key.
	 * 
	 * @param string $key
	 * @param string $value
	 */
	abstract public function registerValue(string $key, string $value);
	
	/**
	 * reset cache
	 * 
	 * @return boolean true on succes, false otherwise
	 */
	abstract public function reset();
	
	/**
	 * get key where comhon configuration object must be registered.
	 * 
	 * @return string
	 */
	abstract public function getConfigKey();
	
	/**
	 * get the prefix that will be used to build key when register/load model
	 * 
	 * @return string
	 */
	abstract public function getModelPrefixKey();
	
	
	
	/**
	 * get model key according given model name
	 * 
	 * @param string $modelName
	 * @return string
	 */
	public function getModelKey(string $modelName) {
		return $this->getModelPrefixKey().str_replace('\\', '-', $modelName);
	}
	
	/**
	 * load configuration object and instanciate Config singleton.
	 * 
	 * @return \Comhon\Object\Config\Config|null return config or null if config is not cached
	 */
	public function loadConfig() {
		if (!$this->hasValue($this->getConfigKey())) {
			return null;
		}
		return Config::loadFromCache($this->getValue($this->getConfigKey()));
	}
	
	/**
	 * register configuration object.
	 * 
	 * @param \Comhon\Object\Config\Config $config
	 */
	public function registerConfig(Config $config) {
		$this->registerValue($this->getConfigKey(), serialize($config));
	}
	
}