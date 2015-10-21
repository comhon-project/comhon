<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\SqlTable;

class ForeignProperty extends Property {

	private $mSerializations = array();
	private $mHasSerializationReturn = false;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pSerialization = array()) {
		parent::__construct($pModel, $pName, $pSerializationName);
		$this->mSerializations = $pSerialization;
		foreach ($this->mSerializations as $lSerializationUnit) {
			if ($lSerializationUnit->hasReturnValue()) {
				if ($this->mHasSerializationReturn) {
					throw new \Exception("can't have more than one serialization with return value");
				}
				$this->mHasSerializationReturn = true;
				break;
			}
		}
	}
	
	public function hasSerializationReturn() {
		return $this->mHasSerializationReturn;
	}
	
	public function save($pObject) {
		$lReturn = null;
		foreach ($this->mSerializations as $lSerializationUnit) {
			if ($lSerializationUnit->hasReturnValue()) {
				$lReturn = $lSerializationUnit->saveObject($pObject, $this->mModel->getModel());
			}else {
				$lSerializationUnit->saveObject($pObject, $this->mModel->getModel());
			}
		}
		return $lReturn;
	}
	
	public function load($pObject, $pId, $pParentModel) {
		// all serializations are equals so we take arbitrarily the first serialization
		if (count($this->mSerializations) > 0) {
			return $this->mSerializations[0]->loadObject($pObject, $pId, $this->getSerializationName(), $pParentModel);
		}
		else {
			return false;
		}
	}
	
	public function getSerializations() {
		return $this->mSerializations;
	}
	
	public function getFirstSerialization() {
		return array_key_exists(0, $this->mSerializations) ? $this->mSerializations[0] : null;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		foreach ($this->mSerializations as $lSerializationUnit) {
			if ($lSerializationUnit->getModel()->getModelName() == $pSerializationType) {
				return true;
			}
		}
		return false;
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
}