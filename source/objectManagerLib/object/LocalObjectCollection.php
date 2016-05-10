<?php
namespace objectManagerLib\object;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\object\SerializationUnit;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\LocalModel;
use objectManagerLib\object\model\ModelContainer;

class LocalObjectCollection {
	
	private $mMap = array();
	
	/**
	 * get Object with MainModel if exists
	 * @param string|integer $pId
	 * @param string $pMainModelName
	 * @return Object|null
	 */
	public function getObject($pId, $pModelName) {
		return array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName]) 
				? $this->mMap[$pModelName][$pId]
				: null;
	}
	
	/**
	 * verify if Object with specified MainModel and id exists in ObjectCollection
	 * @param string|integer $pId
	 * @param string $pMainModelName
	 * @return boolean true if exists
	 */
	public function hasObject($pId, $pModelName) {
		return array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName]);
	}
	
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
	
	public function toObject() {
		$lArray = array();
		foreach ($this->mMap as $lModelName => $lObjectById) {
			$lArray[$lModelName] = array();
			foreach ($lObjectById as $lId => $lObject) {
				$lArray[$lModelName][$lId] = $lObject->toObject();
			}
		}
		return $lArray;
	}
	
	public function toString() {
		return json_encode($this->toObject());
	}
}