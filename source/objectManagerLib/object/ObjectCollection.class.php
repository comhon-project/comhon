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
use objectManagerLib\object\model\ModelContainer;

class ObjectCollection {
	
	const MAIN_MODEL_OBJECT = 'mainModelObject';
	const INCLUDED_OBJECTS  = 'includedObject';
	
	private $mMap = array();
	private $mCurrentMainModelName;
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
		$this->mCurrentMainModelName;
	}
	
	public function getCurrentIdKey() {
		$this->mCurrentId;
	}
	
	public function getCurrentKey() {
		return array($this->mCurrentMainModelName, $this->mCurrentId);
	}
	
	public function setCurrentKey($lMainModelName, $lId) {
		if (isset($this->mMap[$lMainModelName][$lId])) {
			$this->mCurrentMainModelName = $lMainModelName;
			$this->mCurrentId = $lId;
			return true;
		} 
		return false;
	}
	
	public function addObject(Object $pObject, $pUpdateCurrentCollectionKey = true) {
		$lSuccess       = false;
		$lModel         = ($pObject->getModel() instanceof ModelContainer) ? $pObject->getModel()->getModel() : $pObject->getModel();
		$lMainModelName = ($lModel instanceof MainModel) ? $lModel->getModelName() : null;
		
		if (is_null($lMainModelName)) {
			if ($pObject->getModel()->hasUniqueId()) {
				if ($pObject instanceof ObjectArray) {
					$lSuccess = true;
					foreach ($pObject->getValues() as $lObject) {
						$lId = $this->_addLocalObject($lObject);
						if (is_null($lId)) {
							$lSuccess = false;
						}
					}
				} else {
					$lId = $this->_addLocalObject($pObject);
					$lSuccess = !is_null($lId);
				}
			}
		} else {
			$lSuccess = true;
			$lId = null;
			if ($pObject instanceof ObjectArray) {
				foreach ($pObject->getValues() as $lObject) {
					list($lId, $lTempSuccess) = $this->_addMainObject($lObject, $lMainModelName);
					if (!$lTempSuccess) {
						$lSuccess = false;
					}
				}
			} else {
				list($lId, $lSuccess) = $this->_addMainObject($pObject, $lMainModelName);
			}
			if (!is_null($lId) && $pUpdateCurrentCollectionKey) {
				$this->mCurrentId = $lId;
			}
		}
		return $lSuccess;
	}
	
	public function _addMainObject(Object $pObject, $pMainModelName) {
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
		if (!array_key_exists($pMainModelName, $this->mMap)) {
			$this->mMap[$pMainModelName] = array();
		}
		if (!array_key_exists($lId, $this->mMap[$pMainModelName])) {
			$this->mMap[$pMainModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $pObject, self::INCLUDED_OBJECTS => array());
		} else if ($pObject->isLoaded() && !$this->mMap[$pMainModelName][$lId][self::MAIN_MODEL_OBJECT]->isLoaded()) {
			$this->mMap[$pMainModelName][$lId][self::MAIN_MODEL_OBJECT] = $pObject;
		} else {
			$lSuccess = false;
		}
		
		return array($lId, $lSuccess);
	}
	
	public function _addLocalObject(Object $pObject) {
		$lId = $pObject->getValue($pObject->getModel()->getFirstId());
		if (is_null($lId)) {
			return null;
		}
		$lMainModelName = $pObject->getModel()->getMainModelName();
		if (isset($this->mMap[$lMainModelName][$this->mCurrentId])) {
			$lIncludedObjects = &$this->mMap[$lMainModelName][$this->mCurrentId][self::INCLUDED_OBJECTS];
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
	
	public function replaceValue(Object $pParentObject, $pKey, $pUpdateCurrentCollectionKey = true) {
		$lSuccess = false;
		$lObject  = $pParentObject->getValue($pKey); 
		$lModel   = $lObject->getModel();
		
		if (!($lObject instanceof Object) || !($lModel instanceof Model) || (($lModel instanceof MainModel) && !$lModel->hasUniqueId())) {
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
		if ($lModel instanceof MainModel) {
			$lMainModelName = $lModel->getModelName();
			if (isset($this->mMap[$lMainModelName][$lId][self::MAIN_MODEL_OBJECT])) {
				$lRefValues[$pKey] = &$this->mMap[$lMainModelName][$lId][self::MAIN_MODEL_OBJECT];
				$lRefValues2 = $pParentObject->getRefValues();
				$lSuccess = true;
			}
			if ($pUpdateCurrentCollectionKey) {
				$this->mCurrentId = $lId;
			}
		} else {
			$lMainModelName = $pObject->getModel()->getMainModelName();
			if (isset($this->mMap[$lMainModelName][$this->mCurrentId][self::INCLUDED_OBJECTS][$lModel->getModelName()][$lId])) {
				$lRefValues[$pKey] = &$this->mMap[$lMainModelName][$this->mCurrentId][self::INCLUDED_OBJECTS][$lModel->getModelName()][$lId];
				$lSuccess = true;
			}
		}
		return $lSuccess;
	}
	
	public function toString() {
		$lArray = array();
		foreach ($this->mMap as $lMainModelName => $lObjectById) {
			$lArray[$lMainModelName] = array();
			foreach ($lObjectById as $lId => $lObjects) {
				$lArray[$lMainModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $lObjects[self::MAIN_MODEL_OBJECT]->toObject(), self::INCLUDED_OBJECTS => array());
				foreach ($lObjects[self::INCLUDED_OBJECTS] as $lModelName => $lUnserializableObjectById) {
					foreach ($lUnserializableObjectById as $lOtherId => $lObject) {
						$lArray[$lMainModelName][$lId][self::INCLUDED_OBJECTS][$lModelName][$lOtherId] = $lObject->toObject();
					}
				}
			}
		}
		return json_encode($lArray);
	}
}