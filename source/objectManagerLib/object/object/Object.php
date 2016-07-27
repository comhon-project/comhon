<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ModelEnum;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\SimpleModel;

class Object {

	protected $mModel;
	protected $mIsLoaded;
	protected $mValues = array();
	
	
	public final function __construct($pModelName, $lIsLoaded = true) {
		if (($pModelName instanceof Model) || ($pModelName instanceof ModelContainer)) {
			$this->mModel = $pModelName;
		}else {
			$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
		}
		$this->mIsLoaded = $lIsLoaded;
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	public function getValue($pName) {
		return ($this->hasValue($pName)) ? $this->mValues[$pName] : null;
	}
	
	public function getInstanceValue($pPropertyName, $pIsLoaded = true) {
		return $this->getProperty($pPropertyName, true)->getModel()->getObjectInstance($pIsLoaded);
	}
	
	public function resetValues() {
		$this->mValues = array();
	}
	
	public function getValues() {
		return $this->mValues;
	}
	
	public function &getRefValues() {
		return $this->mValues;
	}
	
	public function isLoaded() {
		return $this->mIsLoaded;
	}
	
	public function setLoadStatus() {
		$this->mIsLoaded = true;
	}
	
	public function setUnLoadStatus() {
		$this->mIsLoaded = false;
	}
	
	public function loadValue($pName) {
		if ($this->hasProperty($pName) && ($this->getProperty($pName) instanceof ForeignProperty) && is_object($this->getValue($pName)) && !$this->getValue($pName)->isLoaded()) {
			$lIdValue = $this->getProperty($pName)->isComposition() ? $this->getId() : null;
			if (! $this->getProperty($pName)->loadValue($this->getValue($pName), $lIdValue)) {
				throw new \Exception("cannot load object with name '$pName' and id '".$this->getValue($pName)->getId()."'");
			}
			return $this->getValue($pName);
		}
		return null;
	}
	
	public function loadValueIds($pName) {
		if (is_object($this->getValue($pName)) && !$this->getValue($pName)->isLoaded()) {
			if (! $this->getProperty($pName)->loadValueIds($this->getValue($pName), $this->getId())) {
				throw new \Exception("cannot load object with name '$pName'");
			}
			return $this->getValue($pName);
		}
		return null;
	}
	
	public function getId() {
		$lIdProperties = $this->mModel->getIdProperties();
		if (count($lIdProperties) == 1) {
			return $this->getValue($lIdProperties[0]);
		}
		$lValues = array();
		foreach ($lIdProperties as $lPropertyName) {
			$lValues[] = $this->getValue($lPropertyName);
		}
		return $this->mModel->encodeId($lValues);
	}
	
	public function hasCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName) {
			if(is_null($this->getValue($lPropertyName)) || $this->getValue($lPropertyName) == '') {
				return false;
			}
		}
		return true;
	}
	
	public function verifCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName) {
			if(is_null($this->getValue($lPropertyName)) || $this->getValue($lPropertyName) == '') {
				throw new \Excpetion("id is not complete, property '$lPropertyName' is empty");
			}
		}
	}
	
	public function setValue($pName, $pValue, $pStrict = true) {
			if ($pStrict && !is_null($pValue)) {
				$this->mModel->getProperty($pName, true)->getModel()->verifValue($pValue);
			}
			$this->mValues[$pName] = $pValue;
	}
	
	public function deleteValue($pName) {
		if ($this->hasValue($pName)) {
			unset($this->mValues[$pName]);
		}
	}
	
	/**
	 * instanciate an Object and add it to values
	 * @param unknown $pPropertyName
	 * @param string $pIsLoaded
	 * @return Object
	 */
	public function initValue($pPropertyName, $pIsLoaded = true) {
		$this->mValues[$pPropertyName] = $this->getInstanceValue($pPropertyName, $pIsLoaded);
		return $this->mValues[$pPropertyName];
	}
	
	public function hasValue($pName) {
		return array_key_exists($pName, $this->mValues);
	}
	
	public function hasValues($Names) {
		foreach ($Names as $lName) {
			if (!$this->hasValue($lName)) {
				return false;
			}
		}
		return true;
	}
	
	public function hasProperty($pPropertyName) {
		return $this->mModel->hasProperty($pPropertyName);
	}
	
	public function getProperties() {
		return $this->mModel->getProperties();
	}
	
	public function getPropertiesNames() {
		return array_keys($this->mModel->getProperties());
	}
	
	public function getProperty($pPropertyName, $pThrowException = false) {
		return $this->mModel->getProperty($pPropertyName, $pThrowException);
	}
	
	/*
	 * return true if the object is a new object and doesn't exist in database
	 */
	public function isNew() {
		//TODO
		return true;
	}
	
	public function fromObject($pPhpObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromPhpObject($this, $pPhpObject, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public function toObject($pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
	}
	
	public function fromXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromXml($this, $pXml, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public function toXml($pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		$this->mModel->toXml($this, $lXmlNode, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
		return $lXmlNode;
	}
	
	public function fromSqlDataBase($pRow, $pTimeZone = null, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		$this->mModel->fillObjectFromSqlDatabase($this, $pRow, $pTimeZone, $pUpdateLoadStatus, $pAddUnloadValues);
	}
	
	public function toSqlDataBase($pUseSerializationName = true, $pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->mModel->toSqlDataBase($this, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
	}
	
	/**
	 * 
	 * @param string $pOperation specify it only if object serialization is sqlDatabase
	 * @throws \Exception
	 */
	public function save($pOperation = null) {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->saveObject($this, $pOperation);
	}
	
	/*
	 * return true if $this is equal to $pObject
	 */
	public function isEqual($pObject) {
		if (count($this->mValues) != count($pObject->getValues())) {
			return false;
		}
		foreach ($this->mValues as $lName => $lValue1) {
			if ($pObject->hasValue($lName)) {
				$lValue2 = $pObject->getValue($lName);
				if (($lValue1->getModel()->getModelName() != $lValue2->getModel()->getModelName()) ||  
					(!$lValue1->getModel()->isEqual($lValue1, $lValue2))) {
					return false;
				}
			}else {
				return false;
			}
		}
		return true;
	}
}