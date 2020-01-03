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

use Comhon\Utils\Utils;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Exception\ArgumentException;
use Comhon\Model\Model;

abstract class SerializationFile extends ValidatedSerializationUnit {

	/**
	 * @var \Comhon\Interfacer\StdObjectInterfacer interfacer able to read serialized file content
	 */
	protected $interfacer;
	
	/**
	 * initialize and return interfacer able to read serialized file content
	 *
	 * @return \Comhon\Interfacer\Interfacer
	 */
	abstract protected static function _initInterfacer();
	
	private function getInterfacer() {
		if (is_null($this->interfacer)) {
			$this->interfacer = static::_initInterfacer();
		}
		return $this->interfacer;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::hasIncrementalId()
	 */
	public function hasIncrementalId(Model $model) {
		return false;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @return string
	 */
	protected function _getPath(UniqueObject $object) {
		return $object->getModel()->getSerializationSettings()->getValue('staticPath') 
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
		$content = $object->export($this->getInterfacer());
		if ($this->getInterfacer()->write($content, $path) === false) {
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
		if (!$object->getModel()->hasIdProperties()) {
			throw new SerializationException('Cannot load model without id into file');
		}
		if (!$object->hasCompleteId()) {
			throw new SerializationException('Cannot load object, object id is not complete');
		}
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return false;
		}
		$formatedContent = $this->getInterfacer()->read($path);
		if ($formatedContent === false || is_null($formatedContent)) {
			throw new SerializationException("cannot load file '$path'");
		}
		$object->fill($formatedContent, $this->getInterfacer());
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(UniqueObject $object) {
		if (!$object->getModel()->hasIdProperties()) {
			throw new SerializationException('Cannot delete model without id into file');
		}
		if (!$object->hasCompleteId()) {
			throw new SerializationException('Cannot delete object, object id is not complete');
		}
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return 0;
		}
		if (!Utils::delTree(dirname($path))) {
			$id = $object->getId();
			throw new SerializationException("Cannot delete object '{$object->getModel()->getName()}' with id '$id', failure when try to delete folder '".dirname($path)."'");
		}
		return 1;
	}
	
}