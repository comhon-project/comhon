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
use Comhon\Object\ObjectArray;
use Comhon\Object\ComhonObject;
use Comhon\Model\MainModel;
use Comhon\Serialization\File\XmlFile;
use Comhon\Serialization\File\JsonFile;

abstract class SerializationUnit {

	const UPDATE = 'update';
	const CREATE = 'create';
	
	const SQL_TABLE = 'sqlTable';
	const JSON_FILE = 'jsonFile';
	const XML_FILE  = 'xmlFile';
	
	/** @var ComhonObject */
	protected $settings;
	
	/** @var string */
	protected $inheritanceKey;
	
	/**
	 * 
	 * @param ComhonObject $settings
	 * @param string $inheritanceKey
	 */
	protected function __construct(ComhonObject $settings, $inheritanceKey = null) {
		$this->settings = $settings;
		$this->inheritanceKey = $inheritanceKey;
	}
	
	/**
	 *
	 * @param ComhonObject $settings
	 * @param string $inheritanceKey
	 */
	public static function getInstance(ComhonObject $settings, $inheritanceKey = null, $class = null) {
		if (!is_null($class)) {
			return new $class($settings, $inheritanceKey);
		}
		switch ($settings->getModel()->getName()) {
			case self::SQL_TABLE: return new SqlTable($settings, $inheritanceKey);
			case self::XML_FILE : return new XmlFile($settings, $inheritanceKey);
			case self::JSON_FILE: return new JsonFile($settings, $inheritanceKey);
		}
	}
	
	
	/**
	 *
	 * @return MainModel
	 */
	public function getType() {
		return $this->settings->getModel()->getName();
	}
	
	/**
	 *
	 * @return ComhonObject
	 */
	public function getSettings() {
		return $this->settings;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->inheritanceKey;
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @throws \Exception
	 */
	public function saveObject(ComhonObject $object, $operation = null) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		if (!is_null($operation) && ($operation !== self::CREATE) && ($operation !== self::UPDATE)) {
			throw new \Exception("operation '$operation' not recognized");
		}
		$result = $this->_saveObject($object, $operation);
		$object->resetUpdatedStatus();
		return $result;
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 */
	public function loadObject(ComhonObject $object, $propertiesFilter = null) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_loadObject($object, $propertiesFilter);
	}
	
	/**
	 *
	 * @param ComhonObject $object
	 * @throws \Exception
	 */
	public function deleteObject(ComhonObject $object) {
		if ($this->settings !== $object->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_deleteObject($object);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 */
	protected abstract function _saveObject(ComhonObject $object, $operation = null);
	
	/**
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean
	 */
	protected abstract function _loadObject(ComhonObject $object, $propertiesFilter = null);
	
	/**
	 *
	 * @param unknow $value
	 * @param Model $extendsModel
	 * @return Model
	 */
	public abstract function getInheritedModel($value, Model $extendsModel);
	
	/**
	 * @param ComhonObject $object
	 * @throws \Exception
	 */
	protected abstract function _deleteObject(ComhonObject $object);
	
	/**
	 * 
	 * @param ObjectArray $object
	 * @param string|integer $parentId
	 * @param string[] $aggregationProperties
	 * @param boolean $onlyIds
	 * @throws \Exception
	 */
	public function loadAggregation(ObjectArray $object, $parentId, $aggregationProperties, $onlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}