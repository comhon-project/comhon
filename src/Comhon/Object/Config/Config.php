<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object\Config;

use Comhon\Object\ExtendableObject;
use Comhon\Exception\Config\ConfigFileNotFoundException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ComhonException;

class Config extends ExtendableObject {
	
	/**
	 * @var Config
	 */
	private static $instance;
	
	/**
	 * @var string
	 */
	private static $loadPath = '.' . DIRECTORY_SEPARATOR . 'config.json';
	
	/**
	 * @var string
	 */
	private $config_ad;
	
	/**
	 * verify if singleton instance is initialized
	 * 
	 * @return boolean
	 */
	public static function hasInstance() {
		return isset(self::$instance);
	}
	
	/**
	 * get Config instance
	 *
	 * @throws \Exception
	 * @return \Comhon\Object\Config\Config
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			// ModelNanager will call self::initInstance an register instance
			ModelManager::getInstance();
			
			// if instance is not registered, there is a failure
			if (!isset(self::$instance)) {
				throw new ComhonException('instance is not registered');
			}
		}
		return self::$instance;
	}
	
	/**
	 * initialize Config instance.
	 * DON'T call this function, it must be only used during ModelManager instanciation.
	 *
	 * @throws \Exception
	 * @return \Comhon\Object\Config\Config
	 */
	public static function initInstance(array $config, $config_ad) {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new self();
				self::$instance->_setDirectory($config_ad);
				$interfacer = new AssocArrayInterfacer();
				$interfacer->setPrivateContext(true);
				self::$instance->fill($config, $interfacer);
			} catch (\Exception $e) {
				self::$instance = null;
				throw $e;
			}
		}
		return self::$instance;
	}
	
	/**
	 * 
	 * @param string $cacheValue
	 * @return \Comhon\Object\Config\Config
	 */
	public static function loadFromCache(string $cacheValue) {
		if (isset(self::$instance)) {
			throw new ComhonException('Config singleton is already initialized, it cannot be loaded from cache');
		}
		self::$instance = unserialize($cacheValue);
		self::$instance->getModel()->register();
		
		return self::$instance;
	}
	
	/**
	 * reset singleton - should be called only for testing
	 */
	public static function resetSingleton() {
		self::$instance = null;
	}
	
	/**
	 * set path to config file
	 * 
	 * Warning! this method must be called before singleton instanciation
	 * otherwise it will not be taken into account
	 *
	 * @param string $path path to config file. might be absolute or relative.
	 */
	public static function setLoadPath($path) {
		if (!file_exists($path)) {
			throw new ConfigFileNotFoundException('configuration', 'file', $path);
		}
		self::$loadPath = $path;
	}
	
	/**
	 * get path to config file
	 * 
	 * @return string
	 */
	public static function getLoadPath() {
		return self::$loadPath;
	}

	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ExtendableObject::_getModelName()
	 */
	protected function _getModelName() {
		return 'Comhon\Config';
	}
	
	/**
	 * set path to config directory
	 * 
	 * @param string $path_ad
	 */
	private function _setDirectory($path_ad) {
		$this->config_ad = $path_ad;
	}
	
	/**
	 * get path to config directory
	 */
	public function getDirectory() {
		return $this->config_ad;
	}
	
	/**
	 * get database options
	 * 
	 * @return \Comhon\Object\UniqueObject|null
	 */
	public function getDataBaseOptions() {
		return $this->getValue('database');
	}
	
	/**
	 * get database charset
	 *
	 * @return string
	 */
	public function getDataBaseCharset() {
		return $this->issetValue('database') && $this->getValue('database')->issetValue('charset')
			? $this->getValue('database')->getValue('charset')
			: 'utf8';
	}
	
	/**
	 * get database timezone
	 *
	 * @return string
	 */
	public function getDataBaseTimezone() {
		return $this->issetValue('database') && $this->getValue('database')->issetValue('timezone')
			? $this->getValue('database')->getValue('timezone')
			: 'UTC';
	}
	
	/**
	 * get manifest format
	 *
	 * @return string
	 */
	public function getManifestFormat() {
		return $this->getValue('manifest_format');
	}
	
	/**
	 * get cache settings
	 *
	 * @return string|null
	 */
	public function getCacheSettings() {
		return $this->getValue('cache_settings');
	}
	
	/**
	 * get map namespace prefix to directory to allow manifest autoloading
	 *
	 * @return \Comhon\Object\ComhonArray|null
	 */
	public function getManifestAutoloadList() {
		return $this->issetValue('autoload')
			? $this->getValue('autoload')->getValue('manifest')
			: null;
	}
	
	/**
	 * get map namespace prefix to directory to allow serialization manifest autoloading
	 *
	 * @return \Comhon\Object\ComhonArray|null
	 */
	public function getSerializationAutoloadList() {
		return $this->issetValue('autoload')
			? $this->getValue('autoload')->getValue('serialization')
			: null;
	}
	
	/**
	 * get map namespace prefix to directory to allow options manifest autoloading
	 *
	 * @return \Comhon\Object\ComhonArray|null
	 */
	public function getOptionsAutoloadList() {
		return $this->issetValue('autoload')
			? $this->getValue('autoload')->getValue('options')
			: null;
	}
	
	/**
	 * get path to regex list file
	 *
	 * @params boolean $transform if true and config path is relative, 
	 *         path is transformed to absolute path.
	 *         (path is considered as relative only if path begin by ".")
	 * @return string
	 */
	public function getRegexListPath($transform = true) {
		return $transform
			? $this->transformPath($this->getValue('regex_list'))
			: $this->getValue('regex_list');
	}
	
	/**
	 * get path to directory where sql tables informations are serialized
	 *
	 * @params boolean $transform if true and config path is relative,
	 *         path is transformed to absolute path.
	 *         (path is considered as relative only if path begin by ".")
	 * @return string
	 */
	public function getSerializationSqlTablePath($transform = true) {
		return $transform
			? $this->transformPath($this->getValue('sql_table'))
			: $this->getValue('sql_table');
	}
	
	/**
	 * get path to directory where sql databases informations are serialized
	 *
	 * @params boolean $transform if true and config path is relative,
	 *         path is transformed to absolute path.
	 *         (path is considered as relative only if path begin by ".")
	 * @return string
	 */
	public function getSerializationSqlDatabasePath($transform = true) {
		return $transform
			? $this->transformPath($this->getValue('sql_database'))
			: $this->getValue('sql_database');
	}
	
	/**
	 * transform relative path to absolute path by prefixing relative path with config directory. 
	 * path is considered as relative only if path begin by "."
	 * 
	 * @return string
	 */
	public function transformPath($path) {
		return substr($path, 0, 1) == '.'
				? $this->config_ad . DIRECTORY_SEPARATOR . $path
				: $path;
	}
}