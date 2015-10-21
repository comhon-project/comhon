<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use \stdClass;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;

class Model {
	
	protected static $sInstanceObjectHash = array();

	protected $mModelName;
	private $mProperties;
	private $mObjectClass    = "objectManagerLib\object\object\Object";
	private $mSerializations = array();
	private $mIds            = array();
	protected $mIsLoaded     = false;
	
	
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
			foreach ($this->mProperties as $lProperty) {
				if ($lProperty->isId()) {
					$this->mIds[] = $lProperty->getName();
				}
			}
			if (!is_null($lResult["serializations"])) {
				$this->mSerializations = $lResult["serializations"];
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
	
	public function getObjectInstance($pIsloaded = true) {
		return new $this->mObjectClass($this, $pIsloaded);
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
			if (($lProperty instanceof ForeignProperty) && $lProperty->hasSerializationUnit($pSerializationType)) {
				$lProperties[] = $lProperty;
			}
		}
		return $lProperties;
	}
	
	public function getIds() {
		return $this->mIds;
	}
	
	public function getFirstId() {
		return (count($this->mIds) > 0) ? $this->mIds[0] : null;
	}
	
	public function getSerializations() {
		return $this->mSerializations;
	}
	
	public function getFirstSerialization() {
		return array_key_exists(0, $this->mSerializations) ? $this->mSerializations[0] : null;
	}
	
	public function getSerialization($pIndex) {
		return ($pIndex < count($this->mSerializations)) ? $this->mSerializations[$pIndex] : null;
	}
	
	public function hasSerialization() {
		return is_array($this->mSerializations) && (count($this->mSerializations) > 0);
	}
	
	public function hasSqlTableUnit() {
		foreach ($this->mSerializations as $lSerializationUnit) {
			if ($lSerializationUnit instanceof SqlTable) {
				return true;
			}
		}
		return false;
	}
	
	public function getSqlTableUnit() {
		foreach ($this->mSerializations as $lSerializationUnit) {
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
			return null;
		}
		if ($pExportForeignObject && array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				if (count($this->getIds()) > 0) {
					return $this->toObjectId($pObject, $pUseSerializationName);
				}
				$pExportForeignObject = false;
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		foreach ($pObject->getValues() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lProperty =  $this->getProperty($lKey);
				$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
				$lReturn->$lName = $lProperty->getModel()->toObject($lValue, $pUseSerializationName, $pExportForeignObject);
			}
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	public function toObjectId($pObject, $pUseSerializationName = false) {
		$lPropertyIds = $this->getIds();
		if (count($lPropertyIds) > 0) {
			$lReturn = new stdClass();
			foreach ($lPropertyIds as $lPropertyId) {
				if ($this->hasProperty($lPropertyId) && $pObject->hasValue($lPropertyId)) {
					$lProperty =  $this->getProperty($lPropertyId);
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					$lReturn->$lName = $lProperty->getModel()->toObject($pObject->getValue($lPropertyId), $pUseSerializationName, false);
				}
			}
		}else {
			$lReturn = null;
			trigger_error("Warning cannot export foreign property with model '{$this->mModelName}' because this model doesn't have id");
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
	
	public function fromObject($pPhpObject) {
		$lObject = $this->getObjectInstance();
		
		foreach ($pPhpObject as $lKey => $lPhpValue) {
			if ($this->hasProperty($lKey)) {
				$lObject->setValue($lKey, $this->getPropertyModel($lKey)->fromObject($lPhpValue));
			}
		}
		return $lObject;
	}
	
	public function fromXml($pXml) {
		$lObject = $this->getObjectInstance();
		
		foreach ($pXml->attributes() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lObject->setValue($lKey,  $this->getPropertyModel($lKey)->fromXml((string) $lValue));
			}
		}
		foreach ($pXml->children() as $lChild) {
			$lPropertyName = $lChild->getName();
			if ($this->hasProperty($lPropertyName)) {
				$lObject->setValue($lPropertyName, $this->getPropertyModel($lPropertyName)->fromXml($lChild));
			}
		}
		return $lObject;
	}
	
	public function fromSqlDataBase($pRow) {
		$lObject = $this->getObjectInstance();
		foreach ($this->getProperties() as $lPropertyName => $lProperty) {
			if (array_key_exists($lProperty->getSerializationName(), $pRow)) {
				if ($lProperty instanceof ForeignProperty) {
					$lValue = ($lProperty->getModel()->getModel() instanceof SimpleModel) ? $pRow[$lProperty->getSerializationName()] : json_decode($pRow[$lProperty->getSerializationName()]);
					$lObject->setValue($lPropertyName, $lProperty->getModel()->getModel()->fromIdValue($lValue));
				} else {
					$lValue = ($lProperty->getModel() instanceof SimpleModel) ? $pRow[$lProperty->getSerializationName()] : json_decode($pRow[$lProperty->getSerializationName()]);
					$lObject->setValue($lPropertyName, $lProperty->getModel()->fromObject($lValue));
				}
			}
			else if (($lProperty instanceof ForeignProperty) && !is_null($lProperty->hasSqlTableUnit())) {
				$lForeignModel = $lProperty->getModel()->getModel();
				$lObjectValue = ($lForeignModel instanceof ModelArray) ? new ObjectArray($lForeignModel, false) : new Object($lForeignModel, false);
				$lObject->setValue($lPropertyName, $lObjectValue);
			}
		}
		return $lObject;
	}
	
	public function fromIdValue($pValue) {
		if (is_null($pValue)) {
			return null;
		}
		if (count($lIds = $this->getIds()) != 1) {
			throw new \Exception("model '{$this->mModelName}' must have one and only one id");
		}
		$lObject = $this->getObjectInstance(false);
		$lObject->setValue($lIds[0], $pValue);
		return $lObject;
	}
	
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1->isEqual($pValue2);
	}
}