<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ModelEnum;
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
	
	public function isLoaded() {
		return $this->mIsLoaded;
	}
	
	public function setLoadStatus($pLoadStatus) {
		$this->mIsLoaded = $pLoadStatus;
	}
	
	public function loadValue($pName) {
		if ($this->hasProperty($pName) && ($this->getProperty($pName) instanceof ForeignProperty) && is_object($this->getValue($pName)) && !$this->getValue($pName)->isLoaded()) {
			$lIdValue = $this->getValue($pName)->getId();
			$lSqlTableUnit = $this->getProperty($pName)->getSqlTableUnit();
			if (!is_null($lSqlTableUnit) && $lSqlTableUnit->isComposition($this->getModel(), $this->getProperty($pName)->getSerializationName())) {
				$lIds = $this->getModel()->getIds();
				$lIdValue = $this->getValue($lIds[0]);
			}
			if (! $this->getProperty($pName)->load($this->getValue($pName), $lIdValue, $this->mModel)) {
				throw new \Exception("cannot load object with name '$pName' and id '".$this->getValue($pName)->getId()."'");
			}
			$this->getValue($pName)->setLoadStatus(true);
			return $this->getValue($pName);
		}
		return null;
	}
	
	public function loadValueIds($pName) {
		if (is_object($this->getValue($pName)) && !$this->getValue($pName)->isLoaded()) {
			if (! $this->getProperty($pName)->loadIds($this->getValue($pName), $this->getId(), $this->mModel)) {
				throw new \Exception("cannot load object with name '$pName'");
			}
			$this->getValue($pName)->setLoadStatus(true);
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
	
	public function toXml($pUseSerializationName = false, $pExportForeignObject = false) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		$this->mModel->toXml($this, $lXmlNode, $pUseSerializationName, $pExportForeignObject);
		return $lXmlNode;
	}
	
	public function fromSqlDataBase($pRow, $pAddUnloadValues = true) {
		foreach ($this->getModel()->getProperties() as $lPropertyName => $lProperty) {
			if (array_key_exists($lProperty->getSerializationName(), $pRow)) {
				if (is_null($pRow[$lProperty->getSerializationName()])) {
					continue;
				}
				if ($lProperty instanceof ForeignProperty) {
					$lIsSimpleValue = ($lProperty->getModel()->getModel() instanceof SimpleModel) || ($lProperty->getModel()->getModel() instanceof ModelEnum);
					$lValue = $lIsSimpleValue ? $pRow[$lProperty->getSerializationName()] : json_decode($pRow[$lProperty->getSerializationName()]);
					$this->setValue($lPropertyName, $lProperty->getModel()->getModel()->fromIdValue($lValue));
				} else {
					$lIsSimpleValue = ($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum);
					$lValue = $lIsSimpleValue ? $pRow[$lProperty->getSerializationName()] : json_decode($pRow[$lProperty->getSerializationName()]);
					$this->setValue($lPropertyName, $lProperty->getModel()->fromObject($lValue));
				}
			}
			else if ($pAddUnloadValues && ($lProperty instanceof ForeignProperty) && !is_null($lProperty->hasSqlTableUnit())) {
				$this->initValue($lPropertyName, false);
			}
		}
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