<?php
namespace comhon\object;

use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\object\model\LocalModel;

class LocalObjectCollection extends ObjectCollection {
	
	/**
	 * add object with localModel (only if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if an object with same id already exists
	 * @return boolean true if object is added
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof LocalModel)) {
			throw new \Exception('mdodel must be instance of LocalModel');
		}
		$lReturn = false;
		$lId     = $pObject->getId();
		
		if (!$pObject->getModel()->hasIdProperty() || !$pObject->hasCompleteId()) {
			return $lReturn;
		}
		$pModelName = $pObject->getModel()->getModelName();
		if (!array_key_exists($pModelName, $this->mMap)) {
			$this->mMap[$pModelName] = array();
		}
		if (!array_key_exists($lId, $this->mMap[$pModelName])) {
			$this->mMap[$pModelName][$lId] = $pObject;
			$lReturn = true;
		} else if ($pThrowException) {
			throw new \Exception('object already added');
		}
		
		return $lReturn;
	}
	
}