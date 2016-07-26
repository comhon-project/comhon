<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ModelCustom;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\exception\ControllerParameterException;

abstract class Controller {
	
	protected $mMainObject;
	protected $mParams;
	private   $mInstanceObjectHash = array();
	private   $mPropertyNameStack;
	
	/**
	 * execute controller
	 * @param Oject $pObject
	 * @param array $pParams
	 * @param array $pVisitRootObject
	 * @return unknown|boolean
	 */
	public final function execute(Object $pObject, $pParams = array()) {
		$this->_verifParameters($pParams);
		if (($pObject->getModel() instanceof Model) || ($pObject->getModel() instanceof ModelArray)) {
			$this->mPropertyNameStack = array();
			$this->mMainObject        = $pObject;
			$this->mParams            = $pParams;	

			$this->_init($pObject);
			
			if ($this->_isVisitRootObject()) {
				$lModelName   = $pObject->getModel()->getModelName();
				$lProperty    = new ForeignProperty($pObject->getModel(), $lModelName);
				$lCustomModel = new ModelCustom('modelCustom', array($lProperty));
				$lRootObject  = $lCustomModel->getObjectInstance();
				$lRootObject->setValue($lModelName, $pObject);
				$this->_accept($lRootObject, $lModelName, $lModelName);
			} else {
				$this->_acceptChildren($pObject);
			}
			
			return $this->_finalize($pObject);
		}
		return false;
	}
	
	private function _accept($pParentObject, $pKey, $pPropertyName) {
		if (!is_null($pParentObject->getValue($pKey))) {
			$this->mPropertyNameStack[] = $pPropertyName;
			$lVisitChild = $this->_visit($pParentObject, $pKey, $this->mPropertyNameStack);
			if ($lVisitChild) {
				$this->_acceptChildren($pParentObject->getValue($pKey));
			}
			$this->_postVisit($pParentObject, $pKey, $this->mPropertyNameStack);
			array_pop($this->mPropertyNameStack);
		}
	}
	
	private function _acceptChildren($pObject) {
		if (is_null($pObject)) {
			return;
		}
		if ($pObject->getModel() instanceof ModelArray && $pObject instanceof ObjectArray) {
			$lPropertyName = $pObject->getModel()->getElementName();
			foreach ($pObject->getValues() as $lKey => $lObject) {
				$this->_accept($pObject, $lKey, $lPropertyName);
			}
		}
		else if (!array_key_exists(spl_object_hash($pObject), $this->mInstanceObjectHash)) {
			$this->mInstanceObjectHash[spl_object_hash($pObject)] = $pObject;
			foreach ($pObject->getModel()->getProperties() as $lPropertyName => $lProperty) {
				$lModel = ($lProperty->getModel() instanceof ModelContainer) ? $lProperty->getModel()->getModel() : $lProperty->getModel();
				if (! ($lModel instanceof SimpleModel)) {
					$this->_accept($pObject, $lPropertyName, $lPropertyName);
				}
			}
			unset($this->mInstanceObjectHash[spl_object_hash($pObject)]);
		}
	}
	

	protected function _isVisitRootObject() {
		return true;
	}
	
	private function _verifParameters($pParams) {
		$lParameters = $this->_getMandatoryParameters();
		if (is_array($lParameters)) {
			if (!empty($lParameters)) {
				if (!is_array($pParams)) {
					throw new ControllerParameterException(implode(', ', $lParameters));
				}
				foreach ($lParameters as $lParameterName) {
					if (!array_key_exists($lParameterName, $pParams)) {
						throw new ControllerParameterException($lParameterName);
					}
				}
			}
		} else if (!is_null($lParameters)) {
			throw new ControllerParameterException(null);
		}
	}

	protected abstract function _getMandatoryParameters();

	protected abstract function _init($pObject);
	
	protected abstract function _visit($pParentObject, $pKey, $pPropertyNameStack);
	
	protected abstract function _postVisit($pParentObject, $pKey, $pPropertyNameStack);
	
	protected abstract function _finalize($pObject);
}