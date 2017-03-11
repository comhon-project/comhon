<?php
namespace comhon\object\serialization;

use comhon\model\Model;
use comhon\object\ObjectArray;
use comhon\object\Object;
use comhon\model\MainModel;
use comhon\model\ModelContainer;
use comhon\object\serialization\file\XmlFile;
use comhon\object\serialization\file\JsonFile;

abstract class SerializationUnit {

	const UPDATE = 'update';
	const CREATE = 'create';
	
	const SQL_TABLE = 'sqlTable';
	const JSON_FILE = 'jsonFile';
	const XML_FILE  = 'xmlFile';
	
	/** @var Object */
	protected $mSettings;
	
	/** @var string */
	protected $mInheritanceKey;
	
	/**
	 * 
	 * @param Object $pSettings
	 * @param string $pInheritanceKey
	 */
	private function __construct(Object $pSettings, $pInheritanceKey = null) {
		$this->mSettings = $pSettings;
		$this->mInheritanceKey = $pInheritanceKey;
	}
	
	/**
	 *
	 * @param Object $pSettings
	 * @param string $pInheritanceKey
	 */
	public static function getInstance(Object $pSettings, $pInheritanceKey = null, $pClass = null) {
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
	 * @return Object
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
	 * @param Object $pObject
	 * @throws \Exception
	 */
	public function saveObject(Object $pObject, $pOperation = null) {
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
	 * @param Object $pObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 */
	public function loadObject(Object $pObject, $pPropertiesFilter = []) {
		if ($this->mSettings !== $pObject->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_loadObject($pObject, $pPropertiesFilter);
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @throws \Exception
	 */
	public function deleteObject(Object $pObject) {
		if ($this->mSettings !== $pObject->getModel()->getSerializationSettings()) {
			throw new \Exception('class serialization settings mismatch with parameter Object serialization settings');
		}
		return $this->_deleteObject($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 */
	protected abstract function _saveObject(Object $pObject, $pOperation = null);
	
	/**
	 * @param Object $pObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean
	 */
	protected abstract function _loadObject(Object $pObject, $pPropertiesFilter = []);
	
	/**
	 *
	 * @param unknow $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public abstract function getInheritedModel($pValue, Model $pExtendsModel);
	
	/**
	 * @param Object $pObject
	 * @throws \Exception
	 */
	protected abstract function _deleteObject(Object $pObject);
	
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