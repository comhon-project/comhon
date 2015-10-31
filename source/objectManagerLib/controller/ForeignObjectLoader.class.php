<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\ForeignProperty;

class ForeignObjectLoader extends Controller {

	private $mUnloadSerializedRefValueMap = array();
	private $mLoadCompositions = true;
	
	protected function _init($pObject) {
		if (array_key_exists(0, $this->mParams)) {
			$this->mLoadCompositions = $this->mParams[0];
		}
	}
	
	protected function _visit($pObject, $pParentObject, $pPropertyName) {
		$lObjects = (!is_null($pObject) && ($pObject instanceof ObjectArray)) ? $pObject->getValues() : array($pObject);
		if (!is_null($pParentObject)) {
			$lPropertiesIds = $pParentObject->getProperty($pPropertyName)->getModel()->getIds();
			$lPropertyId = array_key_exists(0, $lPropertiesIds) ? $lPropertiesIds[0] : null;
			foreach ($lObjects as $lObject) {
				if (is_object($lObject) && !$lObject->isLoaded()) {
					$this->_addRefValue($pParentObject, $pPropertyName, $lObject, $lPropertyId);
				}
			}
		}
		return true;
	}
	
	protected function _postVisit($pValue, $pParentObject, $pPropertyName) {}
	
	private function _addRefValue($pParentObject, $pPropertyName, $pObject, $pPropertyId) {
		$lSerializationUnit = $pParentObject->getProperty($pPropertyName)->getFirstSerialization();
		if (!is_null($lSerializationUnit)) {
			$lIdValue = $pObject->getValue($pPropertyId);
			$lSqlTableUnit = $pParentObject->getProperty($pPropertyName)->getSqlTableUnit();
			if (!is_null($lSqlTableUnit) && $lSqlTableUnit->isComposition($pParentObject->getModel(), $pParentObject->getProperty($pPropertyName)->getSerializationName())) {
				if ($this->mLoadCompositions) {
					$lIds = $pParentObject->getModel()->getIds();
					$lSerializationUnit = $lSqlTableUnit;
					$lIdValue = $pParentObject->getModel()->getModelName()."-".$pParentObject->getValue($lIds[0]);
				} else {
					return;
				}
			}
			if (!is_null($lIdValue)) {
				$lKey = spl_object_hash($lSerializationUnit);
				if (!array_key_exists($lKey, $this->mUnloadSerializedRefValueMap)) {
					$this->mUnloadSerializedRefValueMap[$lKey] = array();
				}
				if (!array_key_exists($lIdValue, $this->mUnloadSerializedRefValueMap[$lKey])) {
					$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue] = array();
				}
				$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue][] = array($pParentObject, $pPropertyName);
			}
		}
	}
	
	protected function _finalize($pObject) {
		foreach ($this->mUnloadSerializedRefValueMap as $lKey => $lMap) {
			foreach ($lMap as $lIdValue => $lRefValues) {
				$lLoadedValue = $lRefValues[0][0]->loadValue($lRefValues[0][1]);
				/*for ($i = 1; $i < count($lRefValues); $i++) {
					$lRefValues[$i][0]->setValue($lRefValues[$i][1], $lLoadedValue);
				}*/
			}
		}
		$this->mUnloadSerializedRefValueMap = array();
	}
	
}