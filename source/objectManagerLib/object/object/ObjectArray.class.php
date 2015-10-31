<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\model\ForeignProperty;

class ObjectArray extends Object {

	const __UNLOAD__ = "__UNLOAD__";
	
	public function loadValue($pkey) {
		if (is_object($this->getValue($pkey)) && !$this->getValue($pkey)->isLoaded()) {
			if (! $this->getProperty($pkey)->load($this->getValue($pkey), $this->getValue($pkey)->getId(), $this->mModel->getModel())) {
				throw new \Exception("cannot load object with name '$pkey' and id '".$this->getValue($pkey)->getId()."'");
			}
			$this->getValue($pkey)->setLoadStatus(true);
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
	
	public function fromObject($pPhpObject) {
		foreach ($pArray as $lKey => $lPhpValue) {
			$this->setValue($lKey, $this->mModel->getModel()->fromObject($lPhpValue));
		}
	}
	
	public function toObject($pUseSerializationName = false, $pExportForeignObject = false) {
		return $this->mModel->toObject($this, $pUseSerializationName, $pExportForeignObject);
	}
	
	public function fromXml($pXml) {
		foreach ($pXml->children() as $lChild) {
			$this->pushValue($this->mModel->getModel()->fromXml($lChild));
		}
	}
	
	public function toXml($pUseSerializationName = false, $pExportForeignObject = false) {
		return $this->mModel->toXml($this, $pUseSerializationName, $pExportForeignObject);
	}
	
	public function fromSqlDataBase($pRows, $pAddUnloadValues = true) {
		foreach ($pRows as $lRow) {
			$this->pushValue($this->mModel->getModel()->fromSqlDataBase($lRow, $pAddUnloadValues));
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