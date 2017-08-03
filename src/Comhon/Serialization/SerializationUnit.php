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
use Comhon\Object\ComhonObject;
use Comhon\Model\MainModel;
use Comhon\Serialization\File\XmlFile;
use Comhon\Serialization\File\JsonFile;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\SerializationException;
use Comhon\Exception\ArgumentException;

abstract class SerializationUnit {

	/** @var string update operation */
	const UPDATE = 'update';
	
	/** @var string create operation */
	const CREATE = 'create';
	
	/** @var string sql serialization */
	const SQL_TABLE = 'sqlTable';
	
	/** @var string json file serialization */
	const JSON_FILE = 'jsonFile';
	
	/** @var string xml file serialization */
	const XML_FILE  = 'xmlFile';
	
	/** @var \Comhon\Object\ComhonObject */
	protected $settings;
	
	/** @var string */
	protected $inheritanceKey;
	
	/**
	 * 
	 * @param \Comhon\Object\ObjectUnique $settings
	 * @param string $inheritanceKey
	 */
	protected function __construct(ObjectUnique $settings, $inheritanceKey = null) {
		$this->settings = $settings;
		$this->inheritanceKey = $inheritanceKey;
	}
	
	/**
	 * get serialization unit instance
	 *
	 * @param \Comhon\Object\ObjectUnique $settings
	 * @param string $inheritanceKey
	 * @param string $class
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public static function getInstance(ObjectUnique $settings, $inheritanceKey = null, $class = null) {
		if (!is_null($class)) {
			$lSerializationUnit = new $class($settings, $inheritanceKey);
			if (!($lSerializationUnit instanceof SerializationUnit)) {
				throw new SerializationException('customized serialization should inherit from SerializationUnit');
			}
		}
		switch ($settings->getModel()->getName()) {
			case self::SQL_TABLE: return new SqlTable($settings, $inheritanceKey);
			case self::XML_FILE : return new XmlFile($settings, $inheritanceKey);
			case self::JSON_FILE: return new JsonFile($settings, $inheritanceKey);
		}
	}
	
	
	/**
	 * get serialization unit type (through settings)
	 * 
	 * @return \Comhon\Model\MainModel
	 */
	public function getType() {
		return $this->settings->getModel()->getName();
	}
	
	/**
	 * get serialization unit settings
	 *
	 * @return \Comhon\Object\ObjectUnique
	 */
	public function getSettings() {
		return $this->settings;
	}
	
	/**
	 * get serialization unit inheritance key
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->inheritanceKey;
	}
	
	/**
	 * save specified comhon object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string $operation
	 * @throws \Exception
	 * @return integer number of saved objects
	 */
	public function saveObject(ObjectUnique $object, $operation = null) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new SerializationException('class serialization settings mismatch with parameter Object serialization settings');
		}
		if (!is_null($operation) && ($operation !== self::CREATE) && ($operation !== self::UPDATE)) {
			throw new ArgumentException($operation, [self::CREATE, self::UPDATE], 2);
		}
		$result = $this->_saveObject($object, $operation);
		$object->resetUpdatedStatus();
		return $result;
	}
	
	/**
	 * load specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 * @return boolean true if object is successfully load, false otherwise
	 */
	public function loadObject(ObjectUnique $object, $propertiesFilter = null) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new SerializationException('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_loadObject($object, $propertiesFilter);
	}
	
	/**
	 * delete specified comhon object from serialization according its id
	 *
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return integer number of deleted objects
	 */
	public function deleteObject(ObjectUnique $object) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new SerializationException('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_deleteObject($object);
	}
	
	/**
	 * save specified comhon object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string $operation
	 * @return integer number of saved objects
	 */
	abstract protected function _saveObject(ObjectUnique $object, $operation = null);
	
	/**
	 * load specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if object is successfully load, false otherwise
	 */
	abstract protected function _loadObject(ObjectUnique $object, $propertiesFilter = null);
	
	/**
	 * get inherited model from serialized value
	 *
	 * @param mixed $value
	 * @param \Comhon\Model\Model $baseModel
	 * @return \Comhon\Model\Model
	 */
	abstract public function getInheritedModel($value, Model $baseModel);
	
	/**
	 * delete specified comhon object from serialization according its id
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return integer number of deleted objects
	 */
	abstract protected function _deleteObject(ObjectUnique $object);
	
}