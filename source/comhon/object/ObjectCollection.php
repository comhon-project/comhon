<?php
namespace comhon\object;

use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\utils\Utils;

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
	 * @return boolean true if object is added
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		$lSuccess = false;
		
		if ($pObject->hasCompleteId()) {
			if ($pObject->getModel()->hasIdProperties()) {
				$lModelName = $pObject->getModel()->getModelName();
				$lId = $pObject->getId();
				if (!array_key_exists($lModelName, $this->mMap)) {
					$this->mMap[$lModelName] = array();
				}
				// if object NOT already added, we can add it
				if(!array_key_exists($lId, $this->mMap[$lModelName])) {
					$this->mMap[$lModelName][$lId] = $pObject;
					$lSuccess = true;
				}
				else if ($pThrowException) {
					throw new \Exception('object already added');
				}
			}
		}
		else if ($pThrowException) {
			throw new \Exception('object can\'t be added, object has no id or id is incomplete');
		}
		return $lSuccess;
	}
	
	/**
	 * remove object from collection if exists
	 * @param Object $pObject
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(Object $pObject) {
		if ($pObject->hasCompleteId() && $this->hasObject($pObject->getId(), $pObject->getModel()->getModelName())) {
			unset($this->mMap[$pObject->getModel()->getModelName()][$pObject->getId()]);
			return true;
		}
		return false;
	}
	
	public function toStdObject($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null) {
		$lArray = array();
		foreach ($this->mMap as $lModelName => $lObjectById) {
			$lArray[$lModelName] = array();
			foreach ($lObjectById as $lId => $lObject) {
				$lArray[$lModelName][$lId] = $lObject->toStdObject($pPrivate, $pUseSerializationName, $pTimeZone);
			}
		}
		return $lArray;
	}
	
	public function toString($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null) {
		return json_encode($this->toStdObject($pPrivate, $pUseSerializationName, $pTimeZone));
	}
}