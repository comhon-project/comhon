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

abstract class SerializationFile extends SerializationUnit {

	private $interfacer;
	
	/**
	 *
	 * @return Interfacer
	 */
	abstract protected function _getInterfacer();
	
	/**
	 *
	 * @param ComhonObject $settings
	 * @param string $inheritanceKey
	 */
	protected function __construct(ComhonObject $settings, $inheritanceKey = null) {
		parent::__construct($settings, $inheritanceKey);
		$this->interfacer = $this->_getInterfacer();
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @return string
	 */
	protected function _getPath(ComhonObject $object) {
		return $this->settings->getValue('saticPath') . DIRECTORY_SEPARATOR . $object->getId() . DIRECTORY_SEPARATOR . $this->settings->getValue('staticName');
	}

	/**
	 * @param ComhonObject $object
	 * @param string $operation
	 * @return integer
	 */
	protected function _saveObject(ComhonObject $object, $operation = null) {
		if (!$object->getModel()->hasIdProperties()) {
			throw new \Exception('Cannot save model without id in xml file');
		}
		if (!$object->hasCompleteId()) {
			throw new \Exception('Cannot save object, object id is not complete');
		}
		$path = $this->_getPath($object);
		if (!is_null($operation)) {
			if ($operation == self::CREATE) {
				if (file_exists($path)) {
					throw new \Exception("Cannot save object with id '{$object->getId()}'. try to create file but file already exists");
				}
			} else if ($operation == self::UPDATE) {
				if (!file_exists($path)) {
					return 0;
				}
			}
		}
		if (!file_exists(dirname($path))) {
			if (!mkdir(dirname($path), 0777, true) && !file_exists(dirname($path))) {
				throw new \Exception("Cannot save object with id '{$object->getId()}'. Impossible to create directory '".dirname($path).'\'');
			}
		}
		$content = $object->export($this->interfacer);
		$this->_addInheritanceKey($object, $content);
		if ($this->interfacer->write($content, $path) === false) {
			throw new \Exception("Cannot save object with id '{$object->getId()}'. Creation or filling file failed");
		}
		return 1;
	}
	
	/**
	 *
	 * @param ComhonObject $object
	 * @param mixed $InterfacedObject
	 */
	protected function _addInheritanceKey(ComhonObject $object, $InterfacedObject) {
		if (!is_null($this->getInheritanceKey())) {
			$this->interfacer->setValue($InterfacedObject, $object->getModel()->getName(), $this->getInheritanceKey());
		}
	}
	
	/**
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean
	 */
	protected function _loadObject(ComhonObject $object, $propertiesFilter = null) {
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return false;
		}
		$formatedContent = $this->interfacer->read($path);
		if ($formatedContent === false || is_null($formatedContent)) {
			throw new \Exception("cannot load file '$path'");
		}
		if (!is_null($this->getInheritanceKey())) {
			$extendsModel = $object->getModel();
			$model = $this->getInheritedModel($formatedContent, $extendsModel);
			if ($model !== $extendsModel) {
				$object->cast($model);
			}
		}
		$object->fill($formatedContent, $this->interfacer);
		return true;
	}
	
	/**
	 * @param mixed $value
	 * @param Model $extendsModel
	 * @return Model
	 */
	public function getInheritedModel($value, Model $extendsModel) {
		return $this->interfacer->hasValue($value, $this->inheritanceKey)
			? ModelManager::getInstance()->getInstanceModel($this->interfacer->getValue($value, $this->inheritanceKey))
			: $extendsModel;
	}
	
	/**
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return integer
	 */
	protected function _deleteObject(ComhonObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new \Exception('delete operation require complete id');
		}
		$id = $object->getId();
		if ($id == null || $id == '') {
			throw new \Exception("Cannot delete object '{$object->getModel()->getName()}' with id '$id', object id is empty");
		}
		$path = $this->_getPath($object);
		if (!file_exists($path)) {
			return 0;
		}
		if (!Utils::delTree(dirname($path))) {
			throw new \Exception("Cannot delete object '{$object->getModel()->getName()}' with id '$id'");
		}
		return 1;
	}
	
}