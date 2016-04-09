<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\MainModel;

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
	
	public function resetValues() {
		$this->mValues = array();
	}
	
	public function setValue($pKey, $pValue) {
		$this->mValues[$pKey] = $pValue;
	}
	
	public function pushValue($pValue) {
		$this->mValues[] = $pValue;
	}
	
	public function fromObject($pPhpObject, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		foreach ($pArray as $lKey => $lPhpValue) {
			$this->setValue($lKey, $this->mModel->getModel()->fromObject($lPhpValue));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromXml($pXml, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		foreach ($pXml->children() as $lChild) {
			$this->pushValue($this->mModel->getModel()->fromXml($lChild));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromSqlDataBase($pRows, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		foreach ($pRows as $lRow) {
			$this->pushValue($this->mModel->getModel()->fromSqlDataBase($lRow, $pAddUnloadValues));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function fromSqlDataBaseId($pRows, $pUpdateLoadStatus = true) {
		if (!($this->mModel->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		foreach ($pRows as $lRow) {
			$this->pushValue($this->mModel->getModel()->fromSqlDataBaseId($lRow));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
	public function toObject($pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pMainForeignObjects);
	}
	
	public function toXml($pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		return $this->mModel->toXml($this, $lXmlNode, $pUseSerializationName, $pMainForeignObjects);
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