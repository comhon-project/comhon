<?php
namespace comhon\object\config;

use comhon\object\extendable\Object;
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
	
}