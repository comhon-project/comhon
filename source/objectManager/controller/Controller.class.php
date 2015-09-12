<?php
namespace ObjectManagerLib\objectManager\controller;

use ObjectManagerLib\objectManager\object\object\Object;
use ObjectManagerLib\objectManager\Model\ModelArray;
use ObjectManagerLib\objectManager\Model\SimpleModel;
use ObjectManagerLib\objectManager\Model\ModelContainer;

abstract class Controller {

	protected $mMainObject;
	private $mInstanceObjectHash = array();
	
	public final function execute($pObject) {
		if ($pObject instanceof Object) {
			$this->mMainObject = $pObject;
			$this->_init($pObject);
			$this->_accept($pObject, null, null);
			$lReturn = $this->_finalize($pObject);
			return $lReturn;
		}
		return false;
	}
	
	private function _accept($pValue, $pParentObject, $pPropertyName) {
		$lVisitChild = $this->_visit($pValue, $pParentObject, $pPropertyName);
		if ($lVisitChild) {
			$this->_acceptChildren($pValue);
		}
		$this->_postVisit($pValue, $pParentObject, $pPropertyName);
	}
	
	private function _acceptChildren($pValue) {
		$lValues = $pValue;
		if (!is_array($pValue)) {
			$lValues = array($pValue);
		}
		foreach ($lValues as $lObject) {
			if (!is_null($lObject) && (!array_key_exists(spl_object_hash($lObject), $this->mInstanceObjectHash))) {
				$this->mInstanceObjectHash[spl_object_hash($lObject)] = $lObject;
				foreach ($lObject->getModel()->getProperties() as $lPropertyName => $lProperty) {
					$lModel = ($lProperty->getModel() instanceof ModelContainer) ? $lProperty->getModel()->getModel() : $lProperty->getModel();
					if (! ($lModel instanceof SimpleModel)) {
						$this->_accept($lObject->getValue($lPropertyName), $lObject, $lPropertyName);
					}
				}
				unset($this->mInstanceObjectHash[spl_object_hash($lObject)]);
			}
		}
	}
	
	protected abstract function _init($pObject);
	
	protected abstract function _visit($pValue, $pParentObject, $lPropertyName);
	
	protected abstract function _postVisit($pValue, $pParentObject, $lPropertyName);
	
	protected abstract function _finalize($pObject);
}