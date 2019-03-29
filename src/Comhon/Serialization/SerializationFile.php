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

use Comhon\Model\Model;
use Comhon\Utils\Utils;
use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\UniqueObject;
use Comhon\Exception\SerializationException;
use Comhon\Exception\ArgumentException;

abstract class SerializationFile extends ValidatedSerializationUnit {

	/**
	 * get interfacer able to read serialized file content
	 *
	 * @return \Comhon\Interfacer\Interfacer
	 */
	abstract protected static function _getInterfacer();
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @return string
	 */
	protected function _getPath(UniqueObject $object) {
		return $object->getModel()->getSerializationSettings()->getValue('saticPath') 
			. DIRECTORY_SEPARATOR 
			. $object->getId() 
			. DIRECTORY_SEPARATOR 
			. $object->getModel()->getSerializationSettings()->getValue('staticName');
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_saveObject()
	 */
	protected function _saveObject(UniqueObject $object, $operation = null) {
		if (!$object->getModel()->hasIdProperties()) {
			throw new SerializationException('Cannot save model without id into file');
		}
		if (!$object->hasCompleteId()) {
			throw new SerializationException('Cannot save object, object id is not complete');
		}
		$path = $this->_getPath($object);
		if (!is_null($operation)) {
			if ($operation == self::CREATE) {
				if (file_exists($path)) {
					throw new SerializationException("Cannot save object with id '{$object->getId()}'. try to create file but file already exists");
				}
			} else if ($operation == self::UPDATE) {
				if (!file_exists($path)) {
					return 0;
				}
			} else {
				throw new ArgumentException($operation, [self::CREATE, self::UPDATE], 2);
			}
		}
		if (!file_exists(dirname($path))) {
			if (!mkdir(dirname($path), 0777, true) && !file_exists(dirname($path))) {
				throw new SerializationException("Cannot save object with id '{$object->getId()}'. Impossible to create directory '".dirname($path).'\'');
			}
		}
		$content = $object->export(static::_getInterfacer());
		if (static::_getInterfacer()->write($content, $path) === false) {
			throw new SerializationException("Cannot save object with id '{$object->getId()}'. Creation or filling file failed");
		}
		return 1;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(UniqueObject $object, $propertiesFilter = null) {
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return false;
		}
		$formatedContent = static::_getInterfacer()->read($path);
		if ($formatedContent === false || is_null($formatedContent)) {
			throw new SerializationException("cannot load file '$path'");
		}
		$object->fill($formatedContent, static::_getInterfacer());
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(UniqueObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new SerializationException('delete operation require complete id');
		}
		$id = $object->getId();
		if ($id == null || $id == '') {
			throw new SerializationException("Cannot delete object '{$object->getModel()->getName()}' with id '$id', object id is empty");
		}
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return 0;
		}
		if (!Utils::delTree(dirname($path))) {
			throw new SerializationException("Cannot delete object '{$object->getModel()->getName()}' with id '$id', failure when try to delete folder '".dirname($path)."'");
		}
		return 1;
	}
	
}