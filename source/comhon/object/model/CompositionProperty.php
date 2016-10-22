<?php
namespace comhon\object\model;

use comhon\object\object\SqlTable;
use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class CompositionProperty extends ForeignProperty {
	
	private $mCompositionProperties = null;
	
	public function __construct($pModel, $pName, $pCompositionProperties, $pSerializationName = null) {
		parent::__construct($pModel, $pName, $pSerializationName);
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
	 * @param strong|integer $pParentId
	 * @return boolean true if success
	 */
	public function loadValue(Object $pObjectArray, $pParentId = null) {
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			trigger_error("+++++++++ no serial +++++++++");
			return false;
		}
		return $lSerializationUnit->loadComposition($pObjectArray, $pParentId, $this->mCompositionProperties, false);
	}
	
	/**
	 * 
	 * @param ObjectArray $pObjectArray
	 * @param strong|integer $pParentId
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, $pParentId) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			trigger_error("+++++++++ no serial +++++++++");
			return false;
		}
		return $lSqlTableUnit->loadComposition($pObjectArray, $pParentId, $this->mCompositionProperties, true);
	}
	
}