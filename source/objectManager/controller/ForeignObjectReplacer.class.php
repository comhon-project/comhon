<?php
namespace GenLib\objectManager\controller;

use GenLib\objectManager\object\object\Object;
use GenLib\objectManager\Model\SerializableProperty;
use GenLib\objectManager\object\object\UnloadObject;

class ForeignObjectReplacer extends Controller {

	private $mObjectMap = array(); // array of object group by model [a_model_name => [object_id => object]]
	private $mSerializedObjectMap = array(); // array of object group by serialization [a_serialization_id => [object_id => object]]

	private $mUnloadRefValueMap = array(); // array of value reference group by model [a_model_name => [object_id => object]]
	private $mUnloadSerializedRefValueMap = array(); // array of value reference group by serialization [a_serialization_id => [object_id => object]]
	
	protected function _init($pObject) {
	}
	
	protected function _visit($pValue, $pParentObject, $pPropertyName) {
		$lValues = $pValue;
		if (!is_array($pValue)) {
			$lValues = array($pValue);
		}
		foreach ($lValues as $lObject) {
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
				$lSerialization = $pObject->getModel()->getSerialization();
			}else {
				$lSerialization = $pParentObject->getProperty($pPropertyName)->getSerialization();
			}
			if (($pObject instanceof UnloadObject) && !is_null($pParentObject)) {
				$this->_addRefValue($pParentObject, $pPropertyName, $pObject, $lObjectId[0], $lSerialization);
			} else {
				$this->_addObject($pObject, $lObjectId[0], $lSerialization);
			}
		}
	}
	
	private function _addObject($pObject, $pPropertyId, $pSerialization) {
		if (is_array($pSerialization) && (count($pSerialization) > 0)) {
			$lKey = spl_object_hash($pSerialization[0]);
			$lMap = &$this->mSerializedObjectMap;
		} else {
			$lKey = $pObject->getModel()->getModelName();
			$lMap = &$this->mObjectMap;
		}
		if (!array_key_exists($lKey, $lMap)) {
			$lMap[$lKey] = array();
		}
		$lMap[$lKey][$pObject->getValue($pPropertyId)] = $pObject;
	}
	
	private function _addRefValue($pParentObject, $pPropertyName, $pObject, $pPropertyId, $pSerialization) {
		if (is_array($pSerialization) && (count($pSerialization) > 0)) {
			$lKey = spl_object_hash($pSerialization[0]);
			$lMap = &$this->mUnloadSerializedRefValueMap;
		} else {
			$lKey = $pObject->getModel()->getModelName();
			$lMap = &$this->mUnloadRefValueMap;
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
				foreach ($lRefValues as $lRefValue) {
					if (array_key_exists($lSerializationRef, $this->mSerializedObjectMap) && array_key_exists($lId, $this->mSerializedObjectMap[$lSerializationRef])) {
						$lRefValue[0]->setValue($lRefValue[1], $this->mSerializedObjectMap[$lSerializationRef][$lId]);
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