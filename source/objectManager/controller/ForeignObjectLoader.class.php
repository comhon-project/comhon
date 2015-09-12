<?php
namespace GenLib\objectManager\controller;

use GenLib\objectManager\object\object\Object;
use GenLib\objectManager\Model\SerializableProperty;
use GenLib\objectManager\object\object\UnloadObject;

class ForeignObjectLoader extends Controller {

	private $mUnloadSerializedRefValueMap = array();
	
	protected function _init($pObject) {
	}
	
	protected function _visit($pValue, $pParentObject, $pPropertyName) {
		$lValues = $pValue;
		if (!is_array($pValue)) {
			$lValues = array($pValue);
		}
		if (!is_null($pParentObject)) {
			$lObjectId = $pParentObject->getProperty($pPropertyName)->getModel()->getIds();
			if (count($lObjectId) == 1) {
				foreach ($lValues as $lObject) {
					if ($lObject instanceof UnloadObject) {
						$this->_addRefValue($pParentObject, $pPropertyName, $lObject, $lObjectId[0]);
					}
				}
			}
		}
		return true;
	}
	
	protected function _postVisit($pValue, $pParentObject, $pPropertyName) {}
	
	private function _addRefValue($pParentObject, $pPropertyName, $pObject, $pPropertyId) {
		$lSerialization = $pParentObject->getProperty($pPropertyName)->getSerialization();
		if (is_array($lSerialization) && (count($lSerialization) > 0)) {
			$lKey = spl_object_hash($lSerialization[0]);
			if (!array_key_exists($lKey, $this->mUnloadSerializedRefValueMap)) {
				$this->mUnloadSerializedRefValueMap[$lKey] = array();
			}
			$lIdValue = $pObject->getValue($pPropertyId);
			if (!array_key_exists($lIdValue, $this->mUnloadSerializedRefValueMap[$lKey])) {
				$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue] = array();
			}
			$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue][] = array($pParentObject, $pPropertyName);
		}
	}
	
	protected function _finalize($pObject) {
		foreach ($this->mUnloadSerializedRefValueMap as $lKey => $lMap) {
			foreach ($lMap as $lIdValue => $lRefValues) {
				$lLoadedValue = $lRefValues[0][0]->loadValue($lRefValues[0][1]);
				for ($i = 1; $i < count($lRefValues); $i++) {
					$lRefValues[$i][0]->setValue($lRefValues[$i][1], $lLoadedValue);
				}
			}
		}
		$this->mUnloadSerializedRefValueMap = array();
	}
	
}