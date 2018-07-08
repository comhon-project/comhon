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
use Comhon\Exception\ComhonException;

class Config extends ExtendableObject {
	
	/**
	 * @var Config
	 */
	private static $instance;
	
	/**
	 * @var string
	 */
	private static $loadPath = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'comhon' . DIRECTORY_SEPARATOR . 'config.json';
	
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
			if (!file_exists(self::$loadPath)) {
				throw new \Exception('config file doesn\'t exist or have wrong permissions : ' . self::$loadPath);
			}
			if (substr(self::$loadPath, 0, 1) == '.') {
				self::$loadPath = getcwd() . DIRECTORY_SEPARATOR. self::$loadPath;
			}
			$config_af = self::$loadPath;
			$stdInterfacer = new StdObjectInterfacer();
			$stdInterfacer->setSerialContext(true);
			$stdInterfacer->setPrivateContext(true);
			$jsonConfig = $stdInterfacer->read($config_af);
			if (is_null($jsonConfig) || $jsonConfig === false) {
				throw new ComhonException('failure when try to read comhon config file : ' . self::$loadPath);
			}
			self::$instance = new self();
			self::$instance->fill($jsonConfig, $stdInterfacer);
			self::$instance->setDirectory(dirname($config_af));
		}
		
		return self::$instance;
	}
	
	/**
	 * set path to config file
	 * 
	 * Warning! this method must be called before singleton instanciation
	 * otherwise it will not be taken into account
	 *
	 * @param string $path path to config file. might be absolute or relative.
	 *        path is considered as relative only if path begin by "."
	 */
	public static function setLoadPath($path) {
		self::$loadPath = $path;
	}

	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ExtendableObject::_getModelName()
	 */
	protected function _getModelName() {
		return 'config';
	}
	
	/**
	 * set path to config directory
	 * 
	 * @param unknown $path_ad
	 */
	public function setDirectory($path_ad) {
		$this->config_ad = $path_ad;
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
	 * get path to manifest list file
	 *
	 * @params boolean $transform if true and config path is relative, 
	 *         path is transformed to absolute path.
	 *         (path is considered as relative only if path begin by ".")
	 * @return string
	 */
	public function getManifestListPath($transform = true) {
		return $transform && substr($this->getValue('manifestList'), 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->getValue('manifestList')
			: $this->getValue('manifestList');
	}
	
	/**
	 * get path to serialization list file
	 *
	 * @params boolean $transform if true and config path is relative, 
	 *         path is transformed to absolute path.
	 *         (path is considered as relative only if path begin by ".")
	 * @return string
	 */
	public function getSerializationListPath($transform = true) {
		return $transform && substr($this->getValue('serializationList'), 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->getValue('serializationList')
			: $this->getValue('serializationList');
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