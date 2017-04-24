<?php
namespace comhon\controller;

use comhon\visitor\Visitor;

class ForeignObjectLoader extends Visitor {

	private $mLoadAggregations      = true;
	private $mLoadedValues          = [];
	
	protected function _init($pObject) {
		if (array_key_exists(0, $this->mParams)) {
			$this->mLoadAggregations = $this->mParams[0];
		}
	}
	
	protected function _getMandatoryParameters() {
		return [];
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lVisitChildren = true;
		$lObject = $pParentObject->getValue($pKey);
		if (!is_null($lObject)) {
			$lIsAggregation = $pParentObject->hasProperty($pKey) && $pParentObject->getProperty($pKey)->isAggregation();
			if (!$lObject->isLoaded() && (!$lIsAggregation || $this->mLoadAggregations)) {
				$pParentObject->loadValue($pKey);
				$this->mLoadedValues[spl_object_hash($lObject)] = null;
			}
			$lVisitChildren = !array_key_exists(spl_object_hash($lObject), $this->mLoadedValues);
		}
		return $lVisitChildren;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {
		$this->mLoadedValues = [];
	}
	
}