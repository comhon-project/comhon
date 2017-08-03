<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Restriction;

use Comhon\Object\Config\Config;
use Comhon\Exception\NotExistingRegexException;
use Comhon\Exception\ComhonException;

class RegexCollection {
	
	private  static $_instance;
	
	private $regexs;
	
	/**
	 * get regex collection instance
	 * 
	 * @throws \Exception
	 * @return RegexCollection
	 */
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
			self::$_instance->regexs = json_decode(file_get_contents(Config::getInstance()->getRegexListPath()), true);
			if (!is_array(self::$_instance->regexs)) {
				throw new ComhonException("failure when trying to load regex list '".Config::getInstance()->getRegexListPath()."'");
			}
		}
		return self::$_instance;
	}
	
	
	/**
	 * get regex according specified name
	 * 
	 * @param string $name
	 * @throws \Exception
	 * @return string
	 */
	public function getRegex($name) {
		if (!array_key_exists($name, $this->regexs)) {
			throw new NotExistingRegexException($name);
		}
		return $this->regexs[$name];
	}
	
}