<?php
namespace comhon\model\restriction;

use comhon\object\config\Config;
use comhon\exception\NotExistingRegexException;

class RegexCollection {
	
	private  static $_instance;
	
	private $mRegexs;
	
	/**
	 * 
	 * @throws \Exception
	 * @return RegexCollection
	 */
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
			self::$_instance->mRegexs = json_decode(file_get_contents(Config::getInstance()->getRegexListPath()), true);
			if (!is_array(self::$_instance->mRegexs)) {
				throw new \Exception('failure when try to load regex list');
			}
		}
		return self::$_instance;
	}
	
	
	/**
	 * 
	 * @param string $pName
	 * @throws \Exception
	 * @return string
	 */
	public function getRegex($pName) {
		if (!array_key_exists($pName, $this->mRegexs)) {
			throw new NotExistingRegexException($pName);
		}
		return $this->mRegexs[$pName];
	}
	
}