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

use Comhon\Exception\ComhonException;
use Comhon\Object\Config\Config;
use Comhon\Model\Model;
use Comhon\Exception\ArgumentException;

/**
 * CacheHandler permit to cache comhon models to load them more rapidly.
 * It permit to register comhon configuration object too.
 * This class and inherited class should not be instanciated manually, 
 * it is implicitly used in ModelManager
 */
abstract class CacheHandler {

	/** @var string */
	const DIRECTORY = 'directory';
	
	/**
	 * 
	 * @param string $settings contains the informations required to instanciate a cache handler.
	 *                         it must begin with the handler name followed by a colon, 
	 *                         followed by specific handler informations.
	 *                         for example : "directory:/my/path/to/cache/directory"
	 */
	public static function getInstance(string $settings) {
		$pos = strpos($settings, ':');
		if ($pos === false) {
			throw new ComhonException("invalid cache handler settings : '$settings' (no colon)");
		}
		$name = substr($settings, 0, $pos);
		$specificSettings = substr($settings, $pos + 1);
		
		switch ($name) {
			case self::DIRECTORY:
				return new FileSystemCacheHandler($specificSettings);
				break;
			default:
				throw new ComhonException("invalid cache handler name : '$name'");
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
	 * @return string
	 */
	abstract public function getValue(string $key);
	
	/**
	 * register value into cache according given key.
	 */
	abstract public function setValue(string $key, string $value);
	
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
	 * get key where Comhon\SqlTable and Comhon\Database model must be registered.
	 * 
	 * @return string
	 */
	abstract public function getSqlTableModelKey();
	
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
		$this->setValue($this->getConfigKey(), serialize($config));
	}
	
	/**
	 * load Comhon\SqlTable model and Comhon\SqlDatabase model in same time.
	 * 
	 * @return \Comhon\Model\Model|null return Comhon\SqlTable model or null if model is not cached
	 */
	public function loadSqlTable() {
		if (!$this->hasValue($this->getSqlTableModelKey())) {
			return null;
		}
		/** @var \Comhon\Model\Model $model */
		$model = unserialize($this->getValue($this->getSqlTableModelKey()));
		$model->register();
		
		return $model;
	}
	
	/**
	 * register Comhon\SqlTable model and Comhon\SqlDatabase model in same time.
	 * 
	 * @param \Comhon\Model\Model $sqlTableModel given model must be a 'Comhon\SqlTable'
	 */
	public function registerSqlTable(Model $sqlTableModel) {
		if ($sqlTableModel->getName() !== 'Comhon\SqlTable') {
			throw new ArgumentException($sqlTableModel->getName(), 'Comhon\SqlTable', 1);
		}
		// ensure that database property model is loaded
		$sqlTableModel->getProperty('database')->getModel();
		
		$this->setValue($this->getSqlTableModelKey(), serialize($sqlTableModel));
	}
	
}