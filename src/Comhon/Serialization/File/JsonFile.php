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
use Comhon\Interfacer\StdObjectInterfacer;

class JsonFile extends SerializationFile {
	
	/**
	 * @var string json serialization type
	 */
	const SETTINGS_TYPE = 'Comhon\JsonFile';
	
	/**
	 * @var \Comhon\Serialization\File\JsonFile
	 */
	private static $instance;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInstance()
	 * 
	 * @return \Comhon\Serialization\File\JsonFile
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
	 * @return \Comhon\Interfacer\StdObjectInterfacer
	 */
	protected static function _initInterfacer() {
		$interfacer = new StdObjectInterfacer();
		$interfacer->setSerialContext(true);
		$interfacer->setPrivateContext(true);
		$interfacer->setFlagValuesAsUpdated(false);
		
		return $interfacer;
	}
	
}