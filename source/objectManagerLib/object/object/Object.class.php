<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\SerializableProperty;
/*
 * a model attached to a Table in data base
 */
class Object {
	
	private $mModel;
	private $mValues = array();
	
	/*
	 * $pAttributs is an array of key => value that have been retrieve from database
	 */
	public final function __construct($pModelName) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	public function getValue($pKey) {
		return ($this->hasValue($pKey)) ? $this->mValues[$pKey] : null;
	}
	
	public function getValues() {
		return $this->mValues;
	}
	
	public function loadValue($pName, $pLoadDepth = 0) {
		if ($this->hasProperty($pName) && ($this->getProperty($pName) instanceof SerializableProperty) && is_object($this->getValue($pName)) && ($this->getValue($pName) instanceof UnloadObject)) {
			$lValue = $this->getProperty($pName)->load($this->getValue($pName)->getId(), $pLoadDepth);
			if (is_null($lValue)) {
				throw new \Exception("cannot load object with name : ".$pName);
			}
			$this->setValue($pName, $lValue);
			return $this->getValue($pName);
		}
		return null;
	}
	
	public function getId() {
		$lValues = array();
		foreach ($this->mModel->getIds() as $lPropertyName) {
			$lValues[] = $this->getValue($lPropertyName);
		}
		return count($lValues) > 0 ? implode("-", $lValues) : null;
	}
	
	public function setValue($pKey, $pValue) {
		if ($this->hasProperty($pKey)) {
			$this->mValues[$pKey] = $pValue;
		}
	}
	
	public function hasValue($pKey) {
		return array_key_exists($pKey, $this->mValues);
	}
	
	public function hasValues($Names) {
		foreach ($Names as $lName) {
			if (!$this->hasValue($lName)) {
				return false;
			}
		}
		return true;
	}
	
	public function hasProperty($pKey) {
		if (!is_object($this->mModel)) {
			trigger_error(var_export($this->mModel, true));
		}
		return $this->mModel->hasProperty($pKey);
	}
	
	public function getProperties() {
		return $this->mModel->getProperties();
	}
	
	public function getPropertiesNames() {
		return array_keys($this->mModel->getProperties());
	}
	
	public function getProperty($pKey) {
		return $this->mModel->getProperty($pKey);
	}
	
	/*
	 * return true if the object is a new object and doesn't exist in database
	 */
	public function isNew() {
		//TODO
		return true;
	}
	
	public function fromObject($pPhpObject) {
		foreach ($pPhpObject as $lKey => $lValue) {
			if ($this->getModel()->hasProperty($lKey)) {
				$this->setValue($lKey, $this->getModel()->getPropertyModel($lKey)->fromObject($lValue));
			}
		}
	}
	
	public function toObject($pUseSerializationName = false, $pExportForeignObject = false) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pExportForeignObject);
	}
	
	public function fromXml($pXml) {
		foreach ($pXml->attributes() as $lKey => $lValue) {
			if ($this->getModel()->hasProperty($lKey)) {
				$this->setValue($lKey, $this->getModel()->getPropertyModel($lKey)->fromXml((string) $lValue));
			}
		}
		foreach ($pXml->children() as $lChild) {
			$lPropertyName = $lChild->getName();
			if ($this->getModel()->hasProperty($lPropertyName)) {
				$this->setValue($lPropertyName, $this->getModel()->getPropertyModel($lPropertyName)->fromXml($lChild));
			}
		}
	}
	
	public function toXml() {
		return $this->mModel->toXml($this);
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