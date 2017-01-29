<?php
namespace comhon\object\object\config;

use comhon\object\object\Object;

class Config extends Object {
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			$lConfig_afe = DIRECTORY_SEPARATOR .'etc'.DIRECTORY_SEPARATOR.'comhon'.DIRECTORY_SEPARATOR.'config.json';
			self::$_instance = new self('config');
			self::$_instance->fromStdObject(json_decode(file_get_contents($lConfig_afe)));
		}
		
		return self::$_instance;
	}
	
}