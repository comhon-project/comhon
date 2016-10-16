<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\Model;

class ObjectArray extends Object {

	const __UNLOAD__ = "__UNLOAD__";
	
	public function loadValue($pkey) {
		if (is_object($this->getValue($pkey)) && !$this->getValue($pkey)->isLoaded()) {
			if (! $this->getModel()->getUniqueModel()->loadAndFillObject($this->getValue($pkey))) {
				throw new \Exception("cannot load object ({$this->getModel()->getUniqueModel()->getModelName()}) at index '$pkey' and id '".$this->getValue($pkey)->getId()."'");
			}
			return $this->getValue($pkey);
		}
		return null;
	}
	
	public function getId() {
		return null;
	}
	
	public function setValues($pValue) {
		$this->mValues = $pValue;
	}
	
	public function setValue($pKey, $pValue) {
		$this->mValues[$pKey] = $pValue;
	}
	
	public function pushValue($pValue) {
		$this->mValues[] = $pValue;
	}
	
	public function fromObject($pArray, $pMergeType = Model::MERGE, $pTimeZone = null, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->resetValues();
		$this->setValues($this->mModel->fromObject($pArray, $pMergeType, $pTimeZone)->getValues());
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromXml($pXml, $pMergeType = Model::MERGE, $pTimeZone = null, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->resetValues();
		$this->setValues($this->mModel->fromXml($pXml, $pMergeType, $pTimeZone)->getValues());
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromSqlDataBase($pRows, $pMergeType = Model::MERGE, $pTimeZone = null, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->resetValues();
		$this->setValues($this->mModel->fromSqlDataBase($pRows, $pMergeType, $pTimeZone, $pAddUnloadValues)->getValues());
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromSqlDataBaseId($pRows, $pMergeType = Model::MERGE, $pTimeZone = null, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->resetValues();
		foreach ($pRows as $lRow) {
			$this->pushValue($this->mModel->getModel()->fromSqlDataBaseId($lRow, $pMergeType));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function toObject($pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
	}
	
	public function toXml($pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		return $this->mModel->toXml($this, $lXmlNode, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
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