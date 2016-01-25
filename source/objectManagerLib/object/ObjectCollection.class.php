<?php
namespace objectManagerLib\object;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\object\SerializationUnit;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\object\model\Model;

class ObjectCollection {
	
	const SERIALIZABLE_OBJECT = 'serializableObject';
	const INCLUDED_OBJECTS    = 'includedObject';
	
	private $mMap = array();
	private $mCurrentHash;
	private $mCurrentId;
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	
	private function __construct() {}
	
	public function getCurrentHashKey() {
		$this->mCurrentHash;
	}
	
	public function getCurrentIdKey() {
		$this->mCurrentId;
	}
	
	public function getCurrentKey() {
		return array($this->mCurrentHash, $this->mCurrentId);
	}
	
	public function setCurrentKey($lHash, $lId) {
		if (isset($this->mMap[$lHash][$lId])) {
			$this->mCurrentHash = $lHash;
			$this->mCurrentId = $lId;
			return true;
		} 
		return false;
	}
	
	public function addObject(Object $pObject, $pSerializationUnit, $pUpdateCurrentCollectionKey = true) {
		$lSuccess = false;
		if (!is_null($pSerializationUnit) && !($pSerializationUnit instanceof SerializationUnit)) {
			throw new \Exception('bad parameter, second parameter must be an SerializationUnit');
		}
		$lHash    = is_null($pSerializationUnit) ? null : spl_object_hash($pSerializationUnit);
		
		if (is_null($lHash)) {
			if ($pObject->getModel()->hasUniqueId()) {
				if ($pObject instanceof ObjectArray) {
					$lSuccess = true;
					foreach ($pObject->getValues() as $lObject) {
						$lId = $this->_addObject($lObject);
						if (is_null($lId)) {
							$lSuccess = false;
						}
					}
				} else {
					$lId = $this->_addObject($pObject);
					$lSuccess = !is_null($lId);
				}
			}
		} else {
			$lSuccess = true;
			$lId = null;
			if ($pObject instanceof ObjectArray) {
				foreach ($pObject->getValues() as $lObject) {
					list($lId, $lTempSuccess) = $this->_addSerializableObject($lObject, $lHash);
					if (!$lTempSuccess) {
						$lSuccess = false;
					}
				}
			} else {
				list($lId, $lSuccess) = $this->_addSerializableObject($pObject, $lHash);
			}
			if (!is_null($lId) && $pUpdateCurrentCollectionKey) {
				$this->mCurrentHash = $lHash;
				$this->mCurrentId = $lId;
			}
		}
		return $lSuccess;
	}
	
	public function _addSerializableObject(Object $pObject, $pHash) {
		$lSuccess = true;
		$lId      = '';
		foreach ($pObject->getModel()->getIds() as $lPropertyName) {
			if (!is_null($pObject->getValue($lPropertyName))) {
				$lId .= $pObject->getValue($lPropertyName);
			}
		}
		if ($lId == '') {
			$lId = spl_object_hash($pObject);
		}
		if (!array_key_exists($pHash, $this->mMap)) {
			$this->mMap[$pHash] = array();
		}
		if (!array_key_exists($lId, $this->mMap[$pHash])) {
			$this->mMap[$pHash][$lId] = array(self::SERIALIZABLE_OBJECT => $pObject, self::INCLUDED_OBJECTS => array());
		} else if ($pObject->isLoaded() && !$this->mMap[$pHash][$lId][self::SERIALIZABLE_OBJECT]->isLoaded()) {
			$this->mMap[$pHash][$lId][self::SERIALIZABLE_OBJECT] = $pObject;
		} else {
			$lSuccess = false;
		}
		
		return array($lId, $lSuccess);
	}
	
	public function _addObject(Object $pObject) {
		$lId = $pObject->getValue($pObject->getModel()->getFirstId());
		if (is_null($lId)) {
			return null;
		}
		if (isset($this->mMap[$this->mCurrentHash][$this->mCurrentId])) {
			$lIncludedObjects = &$this->mMap[$this->mCurrentHash][$this->mCurrentId][self::INCLUDED_OBJECTS];
			$pModelName = $pObject->getModel()->getModelName();
			if (!array_key_exists($pModelName, $lIncludedObjects)) {
				$lIncludedObjects[$pModelName] = array();
			}
			if (!array_key_exists($lId, $lIncludedObjects[$pModelName])) {
				$lIncludedObjects[$pModelName][$lId] = $pObject;
			} else if ($pObject->isLoaded() && !$lIncludedObjects[$pModelName][$lId]->isLoaded()) {
				$lIncludedObjects[$pModelName][$lId] = $pObject;
			} else {
				$lId = null;
			}
		} else {
			$lId = null;
		}
		
		return $lId;
	}
	
	public function replaceValue(Object $pParentObject, $pKey, $pSerializationUnit, $pUpdateCurrentCollectionKey = true) {
		$lSuccess = false;
		$lObject  = $pParentObject->getValue($pKey); 
		$lModel   = $lObject->getModel();
		
		if (!($lObject instanceof Object) || !($lModel instanceof Model) || (is_null($pSerializationUnit) && !$lModel->hasUniqueId())) {
			return false;
		}
		$lId      = '';
		foreach ($lObject->getModel()->getIds() as $lPropertyName) {
			if (!is_null($lObject->getValue($lPropertyName))) {
				$lId .= $lObject->getValue($lPropertyName);
			}
		}
		if ($lId == '') {
			return false;
		}
		$lRefValues =& $pParentObject->getRefValues();
		if (is_null($pSerializationUnit)) {
			if (isset($this->mMap[$this->mCurrentHash][$this->mCurrentId][self::INCLUDED_OBJECTS][$lModel->getModelName()][$lId])) {
				$lRefValues[$pKey] = &$this->mMap[$this->mCurrentHash][$this->mCurrentId][self::INCLUDED_OBJECTS][$lModel->getModelName()][$lId];
				$lSuccess = true;
			}
		} else {
			$lHash = spl_object_hash($pSerializationUnit);
			if (isset($this->mMap[$lHash][$lId][self::SERIALIZABLE_OBJECT])) {
				$lRefValues[$pKey] = &$this->mMap[$lHash][$lId][self::SERIALIZABLE_OBJECT];
				$lRefValues2 = $pParentObject->getRefValues();
				$lSuccess = true;
			}
			if ($pUpdateCurrentCollectionKey) {
				$this->mCurrentHash = $lHash;
				$this->mCurrentId = $lId;
			}
		}
		return $lSuccess;
	}
	
	public function toString() {
		$lArray = array();
		foreach ($this->mMap as $lHash => $lObjectById) {
			$lArray[$lHash] = array();
			foreach ($lObjectById as $lId => $lObjects) {
				$lArray[$lHash][$lId] = array(self::SERIALIZABLE_OBJECT => $lObjects[self::SERIALIZABLE_OBJECT]->toObject(), self::INCLUDED_OBJECTS => array());
				foreach ($lObjects[self::INCLUDED_OBJECTS] as $lModelName => $lUnserializableObjectById) {
					foreach ($lUnserializableObjectById as $lOtherId => $lObject) {
						$lArray[$lHash][$lId][self::INCLUDED_OBJECTS][$lModelName][$lOtherId] = $lObject->toObject();
					}
				}
			}
		}
		return json_encode($lArray);
	}
}