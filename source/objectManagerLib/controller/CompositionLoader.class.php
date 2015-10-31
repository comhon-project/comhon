<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;

class CompositionLoader extends Controller {

	private $mUnloadSerializedRefValueMap = array();
	private $mLoadChildren = false;
	
	protected function _init($pObject) {
		if (array_key_exists(0, $this->mParams)) {
			$this->mLoadChildren = $this->mParams[0];
		}
	}
	
	protected function _visit($pObject, $pParentObject, $pPropertyName) {
		if (!is_null($pObject) && ($pObject instanceof ObjectArray) && !is_null($pParentObject)) {
			if (!$pObject->isLoaded()) {
				$this->_addRefValue($pParentObject, $pPropertyName);
			}
		}
		return true;
	}
	
	protected function _postVisit($pValue, $pParentObject, $pPropertyName) {}
	
	private function _addRefValue($pParentObject, $pPropertyName) {
		if (is_null($lSqlTableUnit = $pParentObject->getProperty($pPropertyName)->getSqlTableUnit())) {
			return;	
		}
		if (! $lSqlTableUnit->isComposition($pParentObject->getModel(), $pParentObject->getProperty($pPropertyName)->getSerializationName())) {
			return;
		}
		$lIds = $pParentObject->getModel()->getIds();
		$lIdValue = $pParentObject->getModel()->getModelName()."-".$pParentObject->getValue($lIds[0]);
		if (!is_null($lIdValue)) {
			$lKey = spl_object_hash($lSqlTableUnit);
			if (!array_key_exists($lKey, $this->mUnloadSerializedRefValueMap)) {
				$this->mUnloadSerializedRefValueMap[$lKey] = array();
			}
			if (!array_key_exists($lIdValue, $this->mUnloadSerializedRefValueMap[$lKey])) {
				$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue] = array();
			}
			$this->mUnloadSerializedRefValueMap[$lKey][$lIdValue][] = array($pParentObject, $pPropertyName);
		}
	}
	
	protected function _finalize($pObject) {
		foreach ($this->mUnloadSerializedRefValueMap as $lKey => $lMap) {
			foreach ($lMap as $lIdValue => $lRefValues) {
				if ($this->mLoadChildren) {
					$lLoadedValue = $lRefValues[0][0]->loadValue($lRefValues[0][1]);
				} else {
					$lLoadedValue = $lRefValues[0][0]->loadValueIds($lRefValues[0][1]);
				}
				for ($i = 1; $i < count($lRefValues); $i++) {
					$lRefValues[$i][0]->setValue($lRefValues[$i][1], $lLoadedValue);
				}
			}
		}
		$this->mUnloadSerializedRefValueMap = array();
	}
	
}