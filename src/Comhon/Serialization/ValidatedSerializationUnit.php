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

use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Exception\ArgumentException;

abstract class ValidatedSerializationUnit extends SerializationUnit {
	
	public final function validateSerialization(UniqueObject $object) {
		if (is_null($object->getModel()->getSerialization()->getSerializationUnit())) {
			throw new SerializationException("object with model '{$object->getModel()}' doesn't have serialization unit");
		}
		if (!is_null($object->getModel()->getSerializationSettings())) {
			if ($object->getModel()->getSerializationSettings()->getModel()->getName() !== static::getType()) {
				throw new SerializationException(
					"object with model '{$object->getModel()->getName()}' has wrong serialization. " .
					$object->getModel()->getSerializationSettings()->getModel()->getName() . ' !== ' . static::getType()
				);
			}
		}
		if (!is_null($object->getModel()->getSerialization()->getSerializationUnitClass())) {
			if ($object->getModel()->getSerialization()->getSerializationUnitClass() !== '\\'.static::class) {
				throw new SerializationException(
					"object with model '{$object->getModel()->getName()}' has wrong serialization. " .
					$object->getModel()->getSerialization()->getSerializationUnitClass() . ' !== ' . static::class
				);
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::saveObject()
	 */
	public final function saveObject(UniqueObject $object, $operation = null) {
		$this->validateSerialization($object);
		
		if (!is_null($operation) && ($operation !== self::CREATE) && ($operation !== self::UPDATE)) {
			throw new ArgumentException($operation, [self::CREATE, self::UPDATE], 2);
		}
		$result = $this->_saveObject($object, $operation);
		$object->resetUpdatedStatus();
		return $result;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::loadObject()
	 */
	public final function loadObject(UniqueObject $object, $propertiesFilter = null) {
		$this->validateSerialization($object);
		return $this->_loadObject($object, $propertiesFilter);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::deleteObject()
	 */
	public final function deleteObject(UniqueObject $object) {
		$this->validateSerialization($object);
		return $this->_deleteObject($object);
	}
	
	/**
	 * get serialization unit type
	 *
	 * @return string
	 */
	abstract public static function getType();
	
	/**
	 * save specified comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $operation
	 * @return integer number of saved objects
	 */
	abstract protected function _saveObject(UniqueObject $object, $operation = null);
	
	/**
	 * load specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if object is successfully load, false otherwise
	 */
	abstract protected function _loadObject(UniqueObject $object, $propertiesFilter = null);
	
	/**
	 * delete specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer number of deleted objects
	 */
	abstract protected function _deleteObject(UniqueObject $object);
	
}