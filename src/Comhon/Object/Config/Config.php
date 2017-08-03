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
	
	private  static $_instance;
	
	/**
	 * get Config instance
	 * 
	 * @throws \Exception
	 * @return \Comhon\Object\Config\Config
	 */
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			$config_afe = DIRECTORY_SEPARATOR .'etc'.DIRECTORY_SEPARATOR.'comhon'.DIRECTORY_SEPARATOR.'config.json';
			$stdInterfacer = new StdObjectInterfacer();
			$stdInterfacer->setSerialContext(true);
			$stdInterfacer->setPrivateContext(true);
			$jsonConfig = $stdInterfacer->read($config_afe);
			if (is_null($jsonConfig) || $jsonConfig === false) {
				throw new ComhonException('failure when try to read comhon config file');
			}
			self::$_instance = new self();
			self::$_instance->fill($jsonConfig, $stdInterfacer);
		}
		
		return self::$_instance;
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
	 * @return string
	 */
	public function getManifestListPath() {
		return $this->getValue('manifestList');
	}
	
	/**
	 * get path to serialization list file
	 *
	 * @return string
	 */
	public function getSerializationListPath() {
		return $this->getValue('serializationList');
	}
	
	/**
	 * get path to regex list file
	 *
	 * @return string
	 */
	public function getRegexListPath() {
		return $this->getValue('regexList');
	}
	
}