<?php
namespace ObjectManagerLib\objectManager\Model;

use ObjectManagerLib\objectManager\singleton\InstanceModel;
use \stdClass;
use ObjectManagerLib\objectManager\object\object\SqlTable;

class Model {
	
	protected static $sInstanceObjectHash = array();

	protected $mModelName;	// unique model name
	private $mProperties;   // database attributes type and value
	private $mObjectClass   = "ObjectManagerLib\objectManager\object\object\Object";
	private $mSerialization = array(); // informations for object serialization
	private $mIds           = array(); // list of id properties 
	protected $mIsLoaded    = false;
	
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public function __construct($pModelName, $pLoadModel) {
		$this->mModelName = $pModelName;
		if ($pLoadModel) {
			$this->load();
		}
	}
	
	public function load() {
		if (!$this->mIsLoaded) {
			$lResult = InstanceModel::getInstance()->getProperties($this);
			$this->mProperties = $lResult["properties"];
			$this->mIds = $lResult["ids"];
			if (!is_null($lResult["serialization"])) {
				$this->mSerialization = $lResult["serialization"];
			}
			if (!is_null($lResult["objectClass"])) {
				$this->mObjectClass = $lResult["objectClass"];
			}
			InstanceModel::getInstance()->addInstanceModel($this);
			$this->mIsLoaded = true;
			$this->_init();
		}
	}
	
	protected function _init() {
		// you can overide this function in inherited class to initialize others attributes
	}
	
	public function getObjectCass() {
		return $this->mObjectClass;
	}
	
	public function getObjectInstance() {
		return new $this->mObjectClass($this->mModelName);
	}
	
	public function getModelName() {
		return $this->mModelName;
	}
	
	public function getProperties() {
		return $this->mProperties;
	}
	
	public function getPropertiesNames() {
		return array_keys($this->mProperties);
	}
	
	public function getProperty($pPropertyName) {
		return $this->hasProperty($pPropertyName) ? $this->mProperties[$pPropertyName] : null;
	}
	
	public function getPropertyModel($pPropertyName) {
		return $this->hasProperty($pPropertyName) ? $this->mProperties[$pPropertyName]->getModel() : null;
	}
	
	public function hasProperty($pPropertyName) {
		return array_key_exists($pPropertyName, $this->mProperties);
	}
	
	/**
	 * @param unknown $pSerializationType ("sqlTable", "jsonFile"...)
	 */
	public function getSerializableProperties($pSerializationType) {
		$lProperties = array();
		foreach ($this->mProperties as $lPropertyName => $lProperty) {
			if (($lProperty instanceof SerializableProperty) && $lProperty->hasSerializationUnit($pSerializationType)) {
				$lProperties[] = $lProperty;
			}
		}
		return $lProperties;
	}
	
	public function getIds() {
		return $this->mIds;
	}
	
	public function getSerialization() {
		return $this->mSerialization;
	}
	
	public function hasSerialization() {
		return is_array($this->mSerialization) && (count($this->mSerialization) > 0);
	}
	
	public function hasSqlTableUnit() {
		foreach ($this->mSerialization as $lSerializationUnit) {
			if ($lSerializationUnit instanceof SqlTable) {
				return true;
			}
		}
		return false;
	}
	
	public function getSqlTableUnit() {
		foreach ($this->mSerialization as $lSerializationUnit) {
			if ($lSerializationUnit instanceof SqlTable) {
				return $lSerializationUnit;
			}
		}
		return null;
	}
	
	public function isLoaded() {
		return $this->mIsLoaded;
	}
	
	/*
	 * return true if the object is a new object and doesn't exist in database
	 */
	public function isNew() {
		//TODO
		return true;
	}
	
	public function toObject($pObject, $pUseSerializationName = false, $pExportForeignObject = false) {
		$lReturn = new stdClass();
		if (is_null($pObject)) {
			$lReturn = null;
		}
		else if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			$lReturn = $this->toObjectId($pObject, $pUseSerializationName);
			if (is_null($lReturn)) {
				trigger_error("Warning cannot export object, loop detected");
			}
		}
		else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = $pObject;
			foreach ($pObject->getValues() as $lKey => $lValue) {
				if ($this->hasProperty($lKey)) {
					$lProperty =  $this->getProperty($lKey);
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					$lReturn->$lName = $lProperty->getModel()->toObject($lValue, $pUseSerializationName, $pExportForeignObject);
				}
			}
			unset(self::$sInstanceObjectHash[spl_object_hash($pObject)]);
		}
		return $lReturn;
	}
	
	public function toObjectId($pObject, $pUseSerializationName = false) {
		$lObjectId = $this->getIds();
		if (count($lObjectId) > 0) {
			$lReturn = new stdClass();
			foreach ($lObjectId as $lPropertyId) {
				if ($this->hasProperty($lPropertyId) && $pObject->hasValue($lPropertyId)) {
					$lProperty =  $this->getProperty($lPropertyId);
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					$lReturn->$lName = $lProperty->getModel()->toObject($pObject->getValue($lPropertyId), $pUseSerializationName, false);
				}
			}
		}else {
			$lReturn = null;
			trigger_error("Warning cannot export object id");
		}
		return $lReturn;
	}
	
	public function toXml() {
		// TODO
	}
	
	public function toSqlDataBase($pObject, $pTable, $pPDO) {
		$lQueryColumns = array();
		$lQueryValues = array();
		
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if ($this->hasProperty($lPropertyName) && $lProperty->hasSerializationReturn()) {
				$lProperty =  $this->getProperty($lPropertyName);
				$lQueryColumns[] = $lProperty->getSerializationName();
				$lParams[] = $lProperty->save($lValue, true);
				$lQueryValues[] = "?";
			}
		}
		$lQuery = "INSERT INTO ".$pTable." (".implode(", ", $lQueryColumns).") VALUES (".implode(", ", $lQueryValues).");";
		$pPDO->prepareQuery($lQuery, $lParams);
		trigger_error(var_export($lQuery, true));
		
		return $pPDO->doQuery($lQuery);
	}
	
	/*public function save($pObject, $pSerialization = null) {
		$lReturn = new stdClass();
		if (is_array($pSerialization)) {
			$lSerialzation = $pSerialization;
		}else if (is_array($this->mSerialization)) {
			$lSerialzation = $this->mSerialization;
		}else {
			throw new \Exception("this object can't be serialized");
		}
		foreach ($lSerialzation as $lSerializationUnit) {
			$lSerializationUnit->saveObject($pObject, $this);
		}
		return $lReturn;
	}*/
	
	public function fromObject($pPhpObject, $pLoadDepth = 0) {
		$lObject = $this->getObjectInstance();
		
		foreach ($pPhpObject as $lKey => $lPhpValue) {
			if ($this->hasProperty($lKey)) {
				$lModel = $this->getPropertyModel($lKey);
				$lObject->setValue($lKey, $lModel->fromObject($lPhpValue));
			}
		}
		return $lObject;
	}
	
	public function fromXml($pXml, $pLoadDepth = 0) {
		$lObject = $this->getObjectInstance();
		
		foreach ($pXml->attributes() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lModel = $this->getPropertyModel($lKey);
				$lObject->setValue($lKey, $lModel->fromXml((string) $lValue));
			}
		}
		foreach ($pXml->children() as $lChild) {
			$lPropertyName = $lChild->getName();
			if ($this->hasProperty($lPropertyName)) {
				$lModel = $this->getPropertyModel($lPropertyName);
				$lObject->setValue($lPropertyName, $lModel->fromXml($lChild));
			}
		}
		return $lObject;
	}
	
	/*
	 * set object attributes with $pArray that has been retrieve from dataBase
	 * foreign object can only be set if $pUseUnqiuePropertyName is true (to avoid conflicts with properties name)
	 */
	public function fromSqlDataBase($pRow, $pLoadDepth = 0) {
		$lObject = $this->getObjectInstance();
		
		// first step we set id value if exists (id can be anywhere in row but it must be set in first)
		if (count($lIds = $this->getIds()) > 0) {
			$lPropertyId = $this->getProperty($lIds[0]);
			if (array_key_exists($lPropertyId->getSerializationName(), $pRow)) {
				$lObject->setValue($lPropertyId->getName(), $lPropertyId->getModel()->fromObject($pRow[$lPropertyId->getSerializationName()]));
				//$lObject->setValue($lPropertyId->getName(), $pRow[$lPropertyId->getSerializationName()]);
			}
		}
		// second step we set all values
		foreach ($this->getProperties() as $lPropertyName => $lProperty) {
			if (array_key_exists($lProperty->getSerializationName(), $pRow)) {
				$lObject->setValue($lPropertyName, $lProperty->getModel()->fromObject($pRow[$lProperty->getSerializationName()]));
				//$lObject->setValue($lPropertyName, $pRow[$lProperty->getSerializationName()]);
			}
			else if (($lProperty instanceof SerializableProperty) && !is_null($lProperty->hasSqlTableUnit()) && $pLoadDepth > 0) {
				if (is_null($lProperty->getForeignIds())) {
					throw new \Exception(
							"To load objects, property must have at least one foreign id. \n"
							."Exemple : you have a model person (with database serialization) with property 'chirldren', 'mother_id' and 'father_id'... \n"
							."Property 'children' doesn't represente a column but others row \n"
							."And if we want to automaticaly retrieve children we must know which columns will match with current person id \n"
							."So for property 'children' The foreign ids will be 'mother_id' and 'father_id' \n"
					);
				}
				$lObject->setValue($lPropertyName, $lProperty->load($lObject->getValue($lIds[0]), $pLoadDepth));
			}
		}
		return $lObject;
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1->isEqual($pValue2);
	}
}