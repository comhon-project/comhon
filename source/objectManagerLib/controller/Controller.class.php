<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelContainer;

abstract class Controller {

	protected $mMainObject;
	protected $mParams;
	private   $mInstanceObjectHash = array();
	
	/**
	 * execute controller
	 * @param Oject $pObject
	 * @param array $pParams
	 * @return unknown|boolean
	 */
	public final function execute($pObject, $pParams = null) {
		if ($pObject instanceof Object) {
			$this->mMainObject = $pObject;
			$this->mParams     = $pParams;
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
	
	private function _acceptChildren($pObject) {
		$lObjects = (!is_null($pObject) && ($pObject instanceof ObjectArray)) ? $pObject->getValues() : array($pObject);
		foreach ($lObjects as $lObject) {
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