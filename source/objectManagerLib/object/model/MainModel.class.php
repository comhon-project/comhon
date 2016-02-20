<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\exception\PropertyException;
use \stdClass;

class MainModel extends Model {
	
	private $mSerialization            = null;
	private $mSerializationInitialised = false;
	private $mHasSerializationReturn   = false;
	
	protected final function _setSerialization() {
		if (!$this->mSerializationInitialised) {
			$this->mSerialization = InstanceModel::getInstance()->getSerialization($this);
			if (!is_null($this->mSerialization)) {
				$this->mHasSerializationReturn = $this->mSerialization->hasReturnValue();
			}
			$this->mSerializationInitialised = true;
		}
	}
	
	public function hasLoadedSerialization() {
		return $this->mSerializationInitialised;
	}
	
	public function hasSerializationReturn() {
		return $this->mHasSerializationReturn;
	}
	
	public function getSerialization() {
		return $this->mSerialization;
	}
	
	public function hasSerialization() {
		return !is_null($this->mSerialization);
	}
	
	public function hasSqlTableUnit() {
		return !is_null($this->mSerialization) && ($this->mSerialization instanceof SqlTable);
	}
	
	public function getSqlTableUnit() {
		return !is_null($this->mSerialization) && ($this->mSerialization instanceof SqlTable) ? $this->mSerialization : null;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return !is_null($this->mSerialization) && ($this->mSerialization->getModel()->getModelName() == $pSerializationType);
	}
	
	public function hasSqlTableUnitComposition($pParentModel) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			return false;
		}
		return $lSqlTableUnit->isComposition($pParentModel, $this->getSerializationName());
	}
	
}