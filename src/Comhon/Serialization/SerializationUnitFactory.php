<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization;

use Comhon\Serialization\File\XmlFile;
use Comhon\Serialization\File\JsonFile;
use Comhon\Exception\ComhonException;

abstract class SerializationUnitFactory {

	/**
	 * get serialization unit instance according specified type.
	 * 
	 * available types constants : 
	 * - SqlTable::MODEL_NAME
	 * - XmlFile::MODEL_NAME
	 * - JsonFile::MODEL_NAME
	 *
	 * @param \Comhon\Object\UniqueObject $settings
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public static function getInstance($type) {
		switch ($type) {
			case SqlTable::getModelName() : return SqlTable::getInstance();
			case XmlFile::getModelName()  : return XmlFile::getInstance();
			case JsonFile::getModelName() : return JsonFile::getInstance();
			default: throw new ComhonException('not managed serialization unit type : ' . $type);
		}
	}
	
}
