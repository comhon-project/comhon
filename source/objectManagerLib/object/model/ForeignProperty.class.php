<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\SqlTable;

class ForeignProperty extends Property {
	
	public function hasSerializationReturn() {
		return $this->getUniqueModel()->hasSerializationReturn();
	}
	
	public function save($pObject) {
		$lReturn = null;
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (!is_null($lSerializationUnit)) {
			if ($lSerializationUnit->hasReturnValue()) {
				$lReturn = $lSerializationUnit->saveObject($pObject, $this->getUniqueModel());
			}else {
				$lSerializationUnit->saveObject($pObject, $this->getUniqueModel());
			}
		}
		return $lReturn;
	}
	
	public function load($pObject, $pId, $pParentModel) {
		// all serializations are equals so we take arbitrarily the first serialization
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (!is_null($lSerializationUnit)) {
			return $lSerializationUnit->loadObject($pObject, $pId, $this->getSerializationName(), $pParentModel);
		}
		else {
			return false;
		}
	}
	
	/**
	 * load object ids (only for properties that are serialized in database composition)
	 * @param Object $pObject
	 * @param string $pId
	 * @param Model $pParentModel
	 * @return Object|boolean
	 */
	public function loadIds($pObject, $pId, $pParentModel) {
		if (!is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			return $lSqlTableUnit->loadCompositionIds($pObject, $pId, $this->getSerializationName(), $pParentModel);
		}
		else {
			return false;
		}
	}
	
	public function getSerialization() {
		return $this->getUniqueModel()->getSerialization();
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return $this->getUniqueModel()->hasSerializationUnit();
	}
	
	public function hasSqlTableUnit() {
		return $this->getUniqueModel()->hasSqlTableUnit();
	}
	
	public function getSqlTableUnit() {
		return $this->getUniqueModel()->getSqlTableUnit();
	}
	
	public function hasSqlTableUnitComposition($pParentModel) {
		return $this->getUniqueModel()->hasSqlTableUnitComposition($pParentModel);
	}
}