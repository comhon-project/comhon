<?php
namespace comhon\controller;

use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\model\Model;
use comhon\object\model\ModelArray;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelContainer;
use comhon\object\model\ModelCustom;
use comhon\object\model\Property;
use comhon\object\model\ForeignProperty;
use comhon\exception\ControllerParameterException;

abstract class Controller {
	
	protected $mMainObject;
	protected $mParams;
	private   $mInstanceObjectHash = [];
	private   $mPropertyNameStack;
	
	/**
	 * execute controller
	 * @param Oject $pObject
	 * @param array $pParams
	 * @param array $pVisitRootObject
	 * @return unknown|boolean
	 */
	public final function execute(Object $pObject, $pParams = []) {
		$this->_verifParameters($pParams);
		if (($pObject->getModel() instanceof Model) || ($pObject->getModel() instanceof ModelArray)) {
			$this->mPropertyNameStack = [];
			$this->mMainObject        = $pObject;
			$this->mParams            = $pParams;	

			$this->_init($pObject);
			
			if ($this->_isVisitRootObject()) {
				$lModelName   = $pObject->getModel()->getModelName();
				$lProperty    = new ForeignProperty($pObject->getModel(), $lModelName);
				$lCustomModel = new ModelCustom('modelCustom', [$lProperty]);
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
				if (! ($lProperty->getUniqueModel() instanceof SimpleModel)) {
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