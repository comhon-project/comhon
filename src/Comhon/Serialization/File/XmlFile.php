<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization\File;

use Comhon\Serialization\SerializationFile;
use Comhon\Interfacer\XMLInterfacer;

class XmlFile extends SerializationFile {
	
	/**
	 * @var string xml serialization type
	 */
	const SETTINGS_TYPE = 'Comhon\XmlFile';
	
	/**
	 * @var \Comhon\Serialization\File\XmlFile
	 */
	private static $instance;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInstance()
	 * 
	 * @return \Comhon\Serialization\File\XmlFile
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * get serialization unit type
	 * 
	 * @return string
	 */
	public static function getType() {
		return self::SETTINGS_TYPE;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_initInterfacer()
	 * 
	 * @return \Comhon\Interfacer\XMLInterfacer
	 */
	protected static function _initInterfacer() {
		$interfacer = new XMLInterfacer();
		$interfacer->setSerialContext(true);
		$interfacer->setPrivateContext(true);
		$interfacer->setFlagValuesAsUpdated(false);
		
		return $interfacer;
	}
	
}