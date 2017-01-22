<?php
namespace comhon\object\model;

use comhon\object\object\SqlTable;
use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class CompositionProperty extends ForeignProperty {
	
	private $mCompositionProperties = null;
	
	public function __construct($pModel, $pName, $pCompositionProperties, $pSerializationName = null, $pIsPrivate = false) {
		parent::__construct($pModel, $pName, $pSerializationName, $pIsPrivate, false);
		if (empty($pCompositionProperties)) {
			throw new \Exception('composition must have at least one composition property');
		}
		$this->mCompositionProperties = $pCompositionProperties;
	}
	
	public function isComposition() {
		return true;
	}
	
	public function getCompositionProperties() {
		return $this->mCompositionProperties;
	}
	
	/**
	 *
	 * @param Object $pObjectArray
	 * @param strong|integer $pParentObject
	 * @return boolean true if success
	 */
	public function loadValue(ObjectArray $pObjectArray, Object $pParentObject) {
		if ($pObjectArray->isLoaded()) {
			return false;
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			throw new \Exception('composition has not model with sql serialization');
		}
		return $lSerializationUnit->loadComposition($pObjectArray, $pParentObject->getId(), $this->mCompositionProperties, false);
	}
	
	/**
	 * 
	 * @param ObjectArray $pObjectArray
	 * @param strong|integer $pParentId
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, Object $pParentObject) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			throw new \Exception('composition has not model with sql serialization');
		}
		return $lSqlTableUnit->loadComposition($pObjectArray, $pParentObject->getId(), $this->mCompositionProperties, true);
	}
	
}