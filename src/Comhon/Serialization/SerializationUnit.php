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
use Comhon\Object\UniqueObject;
use Comhon\Exception\ComhonException;

abstract class SerializationUnit {

	/** @var string update operation */
	const UPDATE = 'update';
	
	/** @var string create operation */
	const CREATE = 'create';
	
	/**
	 * get serialization unit instance
	 *
	 * @param \Comhon\Object\UniqueObject $settings
	 * @param string $inheritanceKey
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public static function getInstance($type) {
		switch ($type) {
			case SqlTable::getType() : return new SqlTable();
			case XmlFile::getType() : return new XmlFile();
			case JsonFile::getType() : return new JsonFile();
			default: throw new ComhonException('not managed serialization unit type : ' . $type);
		}
	}
	
	/**
	 * save specified comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $operation
	 * @throws \Exception
	 * @return integer number of saved objects
	 */
	abstract public function saveObject(UniqueObject $object, $operation = null);
	
	/**
	 * load specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 * @return boolean true if object is successfully load, false otherwise
	 */
	abstract public function loadObject(UniqueObject $object, $propertiesFilter = null);
	
	/**
	 * delete specified comhon object from serialization according its id
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer number of deleted objects
	 */
	abstract public function deleteObject(UniqueObject $object);
	
}