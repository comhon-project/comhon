<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\SqlTable;

class SerializableProperty extends Property {

	private $mSerialization = array();
	private $mHasSerializationReturn = false;
	private $mForeignIds = null;
	
	public function __construct($pModel, $pName, $pSerializationName, $pSerialization, $pIsId = false, $pForeignIds = null) {
		parent::__construct($pModel, $pName, $pSerializationName, $pIsId);
		$this->mSerialization = $pSerialization;
		if (is_array($pForeignIds)) {
			$this->mForeignIds = $pForeignIds;
		}
		foreach ($this->mSerialization as $lSerializationUnit) {
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
	
	public function getForeignIds() {
		return $this->mForeignIds;
	}
	
	public function save($pObject) {
		$lReturn = null;
		foreach ($this->mSerialization as $lSerializationUnit) {
			if ($lSerializationUnit->hasReturnValue()) {
				$lReturn = $lSerializationUnit->saveObject($pObject, $this->mModel);
			}else {
				$lSerializationUnit->saveObject($pObject, $this->mModel);
			}
		}
		return $lReturn;
	}
	
	public function load($pId, $pLoadDepth) {
		// all serialization are equals so we take arbitrarily the first serialization
		return $this->mSerialization[0]->loadObject($pId, $this->mModel, $pLoadDepth, $this->mForeignIds);
	}
	
	public function getSerialization() {
		return $this->mSerialization;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		foreach ($this->mSerialization as $lSerializationUnit) {
			if ($lSerializationUnit->getModel()->getModelName() == $pSerializationType) {
				return true;
			}
		}
		return false;
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
}