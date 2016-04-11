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
		return $this->mModel->formatId($lValues);
	}
	
	public function setValue($pName, $pValue) {
		if ($this->hasProperty($pName)) {
			$this->mValues[$pName] = $pValue;
		}
	}
	
	/**
	 * instanciate an Object value (only if model of property is not a SimpleModel or ModelEnum)
	 * @param unknown $pName
	 * @param string $pIsLoaded
	 * @return Object|boolean
	 */
	public function initValue($pName, $pIsLoaded = true) {
		if ($this->hasProperty($pName)) {
			$lPropertyModel = $this->getProperty($pName)->getModel();
			if (!($lPropertyModel instanceof SimpleModel) && !($lPropertyModel instanceof ModelEnum)) {
				$this->mValues[$pName] = $lPropertyModel->getObjectInstance($pIsLoaded);
				return $this->mValues[$pName];
			}
		}
		return false;
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
	
	public function getProperty($pPropertyName) {
		return $this->mModel->getProperty($pPropertyName);
	}
	
	/*
	 * return true if the object is a new object and doesn't exist in database
	 */
	public function isNew() {
		//TODO
		return true;
	}
	
	public function fromObject($pPhpObject, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromPhpObject($this, $pPhpObject, $pUpdateLoadStatus);
	}
	
	public function toObject($pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pMainForeignObjects);
	}
	
	public function fromXml($pXml, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromXml($this, $pXml, $pUpdateLoadStatus);
	}
	
	public function toXml($pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		$this->mModel->toXml($this, $lXmlNode, $pUseSerializationName, $pMainForeignObjects);
		return $lXmlNode;
	}
	
	public function fromSqlDataBase($pRow, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		$this->mModel->fillObjectFromSqlDatabase($this, $pRow, $pUpdateLoadStatus, $pAddUnloadValues);
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