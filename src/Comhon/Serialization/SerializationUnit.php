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
	protected $mSettings;
	
	/** @var string */
	protected $mInheritanceKey;
	
	/**
	 * 
	 * @param ComhonObject $pSettings
	 * @param string $pInheritanceKey
	 */
	protected function __construct(ComhonObject $pSettings, $pInheritanceKey = null) {
		$this->mSettings = $pSettings;
		$this->mInheritanceKey = $pInheritanceKey;
	}
	
	/**
	 *
	 * @param ComhonObject $pSettings
	 * @param string $pInheritanceKey
	 */
	public static function getInstance(ComhonObject $pSettings, $pInheritanceKey = null, $pClass = null) {
		if (!is_null($pClass)) {
			return new $pClass($pSettings, $pInheritanceKey);
		}
		switch ($pSettings->getModel()->getName()) {
			case self::SQL_TABLE: return new SqlTable($pSettings, $pInheritanceKey);
			case self::XML_FILE : return new XmlFile($pSettings, $pInheritanceKey);
			case self::JSON_FILE: return new JsonFile($pSettings, $pInheritanceKey);
		}
	}
	
	
	/**
	 *
	 * @return MainModel
	 */
	public function getType() {
		return $this->mSettings->getModel()->getName();
	}
	
	/**
	 *
	 * @return ComhonObject
	 */
	public function getSettings() {
		return $this->mSettings;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->mInheritanceKey;
	}
	
	/**
	 * 
	 * @param ComhonObject $pObject
	 * @throws \Exception
	 */
	public function saveObject(ComhonObject $pObject, $pOperation = null) {
		if ($this->mSettings !== $pObject->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		if (!is_null($pOperation) && ($pOperation !== self::CREATE) && ($pOperation !== self::UPDATE)) {
			throw new \Exception("operation '$pOperation' not recognized");
		}
		$lResult = $this->_saveObject($pObject, $pOperation);
		$pObject->resetUpdatedStatus();
		return $lResult;
	}
	
	/**
	 * 
	 * @param ComhonObject $pObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 */
	public function loadObject(ComhonObject $pObject, $pPropertiesFilter = null) {
		if ($this->mSettings !== $pObject->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_loadObject($pObject, $pPropertiesFilter);
	}
	
	/**
	 *
	 * @param ComhonObject $pObject
	 * @throws \Exception
	 */
	public function deleteObject(ComhonObject $pObject) {
		if ($this->mSettings !== $pObject->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_deleteObject($pObject);
	}
	
	/**
	 * 
	 * @param ComhonObject $pObject
	 */
	protected abstract function _saveObject(ComhonObject $pObject, $pOperation = null);
	
	/**
	 * @param ComhonObject $pObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean
	 */
	protected abstract function _loadObject(ComhonObject $pObject, $pPropertiesFilter = null);
	
	/**
	 *
	 * @param unknow $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public abstract function getInheritedModel($pValue, Model $pExtendsModel);
	
	/**
	 * @param ComhonObject $pObject
	 * @throws \Exception
	 */
	protected abstract function _deleteObject(ComhonObject $pObject);
	
	/**
	 * 
	 * @param ObjectArray $pObject
	 * @param string|integer $pParentId
	 * @param string[] $pAggregationProperties
	 * @param boolean $pOnlyIds
	 * @throws \Exception
	 */
	public function loadAggregation(ObjectArray $pObject, $pParentId, $pAggregationProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}