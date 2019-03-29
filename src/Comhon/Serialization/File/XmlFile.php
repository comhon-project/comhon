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
	 * 
	 * @var \Comhon\Interfacer\XMLInterfacer
	 */
	private static $interfacer;
	
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
	 * @see \Comhon\Serialization\SerializationFile::_getInterfacer()
	 * 
	 * @return \Comhon\Interfacer\XMLInterfacer
	 */
	protected static function _getInterfacer() {
		if (is_null(self::$interfacer)) {
			self::$interfacer = new XMLInterfacer();
			self::$interfacer->setSerialContext(true);
			self::$interfacer->setPrivateContext(true);
			self::$interfacer->setFlagValuesAsUpdated(false);
		}
		return self::$interfacer;
	}
	
}