<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\ForeignProperty;

class ForeignObjectReplacer extends Controller {

	private $mObjectMap = array(); // array of object group by model [a_model_name => [object_id => object]]
	private $mSerializedObjectMap = array(); // array of object group by serialization [a_serialization_id => [object_id => object]]

	private $mUnloadRefValueMap = array(); // array of value reference group by model [a_model_name => [object_id => object]]
	private $mUnloadSerializedRefValueMap = array(); // array of value reference group by serialization [a_serialization_id => [object_id => object]]
	
	protected function _init($pObject) {
	}
	
	protected function _visit($pObject, $pParentObject, $pPropertyName) {
		$lObjects = (!is_null($pObject) && ($pObject instanceof ObjectArray)) ? $pObject->getValues() : array($pObject);
		foreach ($lObjects as $lObject) {
			if (!is_null($lObject)) {
				$this->_populateMap($lObject, $pParentObject, $pPropertyName);
			}
		}
		return true;
	}
	
	protected function _postVisit($pValue, $pParentObject, $pPropertyName) {
		
	}
	
	private function _populateMap($pObject, $pParentObject, $pPropertyName) {
		$lObjectId = $pObject->getModel()->getIds();
		if ((count($lObjectId) == 1) && !is_null($pObject->getValue($lObjectId[0]))) {
			if (is_null($pParentObject)) {
				$lSerializationUnit = $pObject->getModel()->getFirstSerialization();
			}else {
				$lSerializationUnit = $pParentObject->getProperty($pPropertyName)->getFirstSerialization();
			}
			if (is_object($pObject) && !$pObject->isLoaded() && !is_null($pParentObject)) {
				$this->_addRefValue($pParentObject, $pPropertyName, $pObject, $lObjectId[0], $lSerializationUnit);
			} else {
				$this->_addObject($pObject, $lObjectId[0], $lSerializationUnit);
			}
		}
	}
	
	private function _addObject($pObject, $pPropertyId, $pSerializationUnit) {
		if (is_null($pSerializationUnit)) {
			$lKey = $pObject->getModel()->getModelName();
			$lMap = &$this->mObjectMap;
		} else {
			$lKey = spl_object_hash($pSerializationUnit);
			$lMap = &$this->mSerializedObjectMap;
		}
		if (!array_key_exists($lKey, $lMap)) {
			$lMap[$lKey] = array();
		}
		$lMap[$lKey][$pObject->getValue($pPropertyId)] = $pObject;
	}
	
	private function _addRefValue($pParentObject, $pPropertyName, $pObject, $pPropertyId, $pSerializationUnit) {
		if (is_null($pSerializationUnit)) {
			$lKey = $pObject->getModel()->getModelName();
			$lMap = &$this->mUnloadRefValueMap;
		} else {
			$lKey = spl_object_hash($pSerializationUnit);
			$lMap = &$this->mUnloadSerializedRefValueMap;
		}
		if (!array_key_exists($lKey, $lMap)) {
			$lMap[$lKey] = array();
		}
		$lIdValue = $pObject->getValue($pPropertyId);
		if (!array_key_exists($lIdValue, $lMap[$lKey])) {
			$lMap[$lKey][$lIdValue] = array();
		}
		$lMap[$lKey][$lIdValue][] = array($pParentObject, $pPropertyName);
	}
	
	protected function _finalize($pObject) {
		foreach ($this->mUnloadRefValueMap as $lModelName => $lRefValuesMapId) {
			foreach ($lRefValuesMapId as $lId => $lRefValues) {
				foreach ($lRefValues as $lRefValue) {
					if (array_key_exists($lModelName, $this->mObjectMap) && array_key_exists($lId, $this->mObjectMap[$lModelName])) {
						$lRefValue[0]->setValue($lRefValue[1], $this->mObjectMap[$lModelName][$lId]);
					}
				}
			}
		}
		foreach ($this->mUnloadSerializedRefValueMap as $lSerializationRef => $lRefValuesMapId) {
			foreach ($lRefValuesMapId as $lId => $lRefValues) {
				if (array_key_exists($lSerializationRef, $this->mSerializedObjectMap) && array_key_exists($lId, $this->mSerializedObjectMap[$lSerializationRef])) {
					foreach ($lRefValues as $lRefValue) {
						$lRefValue[0]->setValue($lRefValue[1], $this->mSerializedObjectMap[$lSerializationRef][$lId]);
					}
				}
				else {
					$lFirstObject = $lRefValues[0][0]->getValue($lRefValues[0][1]);
					for ($i = 1; $i < count($lRefValues); $i++) {
						$lRefValues[$i][0]->setValue($lRefValues[$i][1], $lFirstObject);
					}
				}
			}
		}
		$this->mObjectMap = array();
		$this->mSerializedObjectMap = array();
		$this->mUnloadRefValueMap = array();
		$this->mUnloadSerializedRefValueMap = array();
	}
	
}