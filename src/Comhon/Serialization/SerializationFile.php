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
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\SerializationException;
use Comhon\Exception\ArgumentException;

abstract class SerializationFile extends SerializationUnit {

	/** @var \Comhon\Interfacer\Interfacer */
	private $interfacer;
	
	/**
	 * get interfacer able to read serialized file content
	 *
	 * @return \Comhon\Interfacer\Interfacer
	 */
	abstract protected function _getInterfacer();
	
	/**
	 *
	 * @param \Comhon\Object\ObjectUnique $settings
	 * @param string $inheritanceKey
	 */
	protected function __construct(ObjectUnique $settings, $inheritanceKey = null) {
		parent::__construct($settings, $inheritanceKey);
		$this->interfacer = $this->_getInterfacer();
	}
	
	/**
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @return string
	 */
	protected function _getPath(ObjectUnique $object) {
		return $this->settings->getValue('saticPath') . DIRECTORY_SEPARATOR . $object->getId() . DIRECTORY_SEPARATOR . $this->settings->getValue('staticName');
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_saveObject()
	 */
	protected function _saveObject(ObjectUnique $object, $operation = null) {
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
		$content = $object->export($this->interfacer);
		$this->_addInheritanceKey($object, $content);
		if ($this->interfacer->write($content, $path) === false) {
			throw new SerializationException("Cannot save object with id '{$object->getId()}'. Creation or filling file failed");
		}
		return 1;
	}
	
	/**
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param mixed $InterfacedObject
	 */
	protected function _addInheritanceKey(ComhonObject $object, $InterfacedObject) {
		if (!is_null($this->getInheritanceKey())) {
			$this->interfacer->setValue($InterfacedObject, $object->getModel()->getName(), $this->getInheritanceKey());
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(ObjectUnique $object, $propertiesFilter = null) {
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return false;
		}
		$formatedContent = $this->interfacer->read($path);
		if ($formatedContent === false || is_null($formatedContent)) {
			throw new SerializationException("cannot load file '$path'");
		}
		if (!is_null($this->getInheritanceKey())) {
			$baseModel = $object->getModel();
			$model = $this->getInheritedModel($formatedContent, $baseModel);
			if ($model !== $baseModel) {
				$object->cast($model);
			}
		}
		$object->fill($formatedContent, $this->interfacer);
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInheritedModel()
	 */
	public function getInheritedModel($value, Model $baseModel) {
		return $this->interfacer->hasValue($value, $this->inheritanceKey)
			? ModelManager::getInstance()->getInstanceModel($this->interfacer->getValue($value, $this->inheritanceKey))
			: $baseModel;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(ObjectUnique $object) {
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