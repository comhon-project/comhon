<?php
namespace comhon\object;

use comhon\object\object\Object;
use comhon\object\model\Model;

class ObjectCollection {
	
	protected $mMap = array();
	
	/**
	 * get Object with Model if exists
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @return Object|null
	 */
	public function getObject($pId, $pModelName) {
		return array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName]) 
				? $this->mMap[$pModelName][$pId]
				: null;
	}
	
	/**
	 * verify if Object with specified Model and id exists in ObjectCollection
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @return boolean true if exists
	 */
	public function hasObject($pId, $pModelName) {
		return array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName]);
	}
	
	/**
	 * get all Objects with specified Model if exists
	 * @param string $pModelName
	 * @return Object|null
	 */
	public function getModelObjects($pModelName) {
		return array_key_exists($pModelName, $this->mMap) ? $this->mMap[$pModelName] : null;
	}
	
	/**
	 * add object with Model (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		
		if ($pObject->hasCompleteId()) {
			if ($pObject->getModel()->hasIdProperty()) {
				$lModelName = $pObject->getModel()->getModelName();
				$lId = $pObject->getId();
				if (!array_key_exists($lModelName, $this->mMap)) {
					$this->mMap[$lModelName] = array();
				}
				// if object NOT already added, we can add it and create attached LocalObjectCollection
				if(!array_key_exists($lId, $this->mMap[$lModelName])) {
					$this->mMap[$lModelName][$lId] = $pObject;
				}
				// else if must throw exception => throw exception
				else if ($pThrowException) {
					throw new \Exception('object already added');
				}
			}
		}
		// else if must throw exception => throw exception
		else if ($pThrowException) {
			trigger_error(json_encode($pObject->toObject()));
			trigger_error($pObject->getModel()->getModelName());
			trigger_error(json_encode($pObject->getModel()->getIdproperties()));
			throw new \Exception('object can\'t be added, object has no id or id is incomplete');
		}
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