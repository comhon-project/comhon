<?php
namespace comhon\object\config;

use comhon\object\extendable\Object;
use comhon\object\Object as AbstractObject;
use comhon\interfacer\StdObjectInterfacer;

class Config extends Object {
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			$lConfig_afe = DIRECTORY_SEPARATOR .'etc'.DIRECTORY_SEPARATOR.'comhon'.DIRECTORY_SEPARATOR.'config.json';
			$lStdInterfacer = new StdObjectInterfacer();
			$lStdInterfacer->setSerialContext(true);
			$lStdInterfacer->setPrivateContext(true);
			$lJsonConfig = $lStdInterfacer->read($lConfig_afe);
			if (is_null($lJsonConfig) || $lJsonConfig === false) {
				throw new \Exception('failure when try to read comhon config file');
			}
			self::$_instance = new self();
			self::$_instance->fillObject($lJsonConfig, $lStdInterfacer);
		}
		
		return self::$_instance;
	}
	
	protected function _getModelName() {
		return 'config';
	}
	
	/**
	 * 
	 * @return AbstractObject|null
	 */
	public function getDataBaseOptions() {
		return $this->getValue('database');
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDataBaseCharset() {
		return ($this->getValue('database') instanceof AbstractObject) && $this->getValue('database')->hasValue('charset')
			? $this->getValue('database')->getValue('charset')
			: 'utf8';
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDataBaseTimezone() {
		return ($this->getValue('database') instanceof AbstractObject) && $this->getValue('database')->hasValue('timezone')
		? $this->getValue('database')->getValue('timezone')
		: 'UTC';
	}
	
	/**
	 *
	 * @return string
	 */
	public function getManifestListPath() {
		return $this->getValue('manifestList');
	}
	
	/**
	 *
	 * @return string
	 */
	public function getSerializationListPath() {
		return $this->getValue('serializationList');
	}
	
	/**
	 *
	 * @return string
	 */
	public function getRegexListPath() {
		return $this->getValue('regexList');
	}
	
}