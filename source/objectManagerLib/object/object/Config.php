<?php
namespace objectManagerLib\object\object;

class Config extends Object {
	
	const CONFIG_AFE = '/etc/comhon/config.json';
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self('config');
			self::$_instance->fromObject(json_decode(file_get_contents(self::CONFIG_AFE)));
		}
		
		return self::$_instance;
	}
	
}