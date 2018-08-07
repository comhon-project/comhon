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
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\ConfigMalformedException;
use Comhon\Exception\ConfigFileNotFoundException;

class Config extends ExtendableObject {
	
	/**
	 * @var Config
	 */
	private static $instance;
	
	/**
	 * @var string
	 */
	private static $loadPath = './config.json';
	
	/**
	 * @var string
	 */
	private $config_ad;
	
	/**
	 * get Config instance
	 *
	 * @throws \Exception
	 * @return \Comhon\Object\Config\Config
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				$instance = new self();
			
				// during new config instanciation, ModelManager singleton might be instanciated for the first time
				// in this case ModelManager instanciation need config singleton but it is currently instanciating 
				// (at this step self::$instance is empty)
				// so config singleton might be instanciated two times, so we have to verify a second time
				// if self::$instance is set to avoid to affect self::$instance a second time
				if (!isset(self::$instance)) {
					self::$instance = $instance;
					$config_af = realpath(self::$loadPath);
					if ($config_af === false) {
						throw new ConfigFileNotFoundException('configuration', 'file', self::$loadPath);
					}
					$stdInterfacer = new StdObjectInterfacer();
					$stdInterfacer->setSerialContext(true);
					$stdInterfacer->setPrivateContext(true);
					$jsonConfig = $stdInterfacer->read($config_af);
					if (is_null($jsonConfig) || $jsonConfig === false) {
						throw new ConfigMalformedException($config_af);
					}
					self::$instance->fill($jsonConfig, $stdInterfacer);
					self::$instance->_setDirectory(dirname($config_af));
				}
			} catch (\Exception $e) {
				self::$instance = null;
				throw $e;
			}
		}
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
	 * @param unknown $path_ad
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
	 * @return \Comhon\Object\ObjectUnique|null
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
		return ($this->getValue('database') instanceof ComhonObject) && $this->getValue('database')->hasValue('charset')
			? $this->getValue('database')->getValue('charset')
			: 'utf8';
	}
	
	/**
	 * get database timezone
	 *
	 * @return string
	 */
	public function getDataBaseTimezone() {
		return ($this->getValue('database') instanceof ComhonObject) && $this->getValue('database')->hasValue('timezone')
			? $this->getValue('database')->getValue('timezone')
			: 'UTC';
	}
	
	/**
	 * get manifest format
	 *
	 * @return string
	 */
	public function getManifestFormat() {
		return $this->getValue('manifestFormat');
	}
	
	/**
	 * get map namespace prefix to directory to allow manifest autoloading
	 *
	 * @return string[]
	 */
	public function getManifestAutoloadList() {
		return ($this->getValue('autoload') instanceof ComhonObject)
			? $this->getValue('autoload')->getValue('manifest')
			: null;
	}
	
	/**
	 * get map namespace prefix to directory to allow serialization manifest autoloading
	 *
	 * @return string[]
	 */
	public function getSerializationAutoloadList() {
		return ($this->getValue('autoload') instanceof ComhonObject)
			? $this->getValue('autoload')->getValue('serialization')
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
		return $transform && substr($this->getValue('regexList'), 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->getValue('regexList')
			: $this->getValue('regexList');
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
		return $transform && substr($this->getValue('sqlTable'), 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->getValue('sqlTable')
			: $this->getValue('sqlTable');
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
		return $transform && substr($this->getValue('sqlDatabase'), 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->getValue('sqlDatabase')
			: $this->getValue('sqlDatabase');
	}
}