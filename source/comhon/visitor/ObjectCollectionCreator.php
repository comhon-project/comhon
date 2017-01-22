<?php
namespace comhon\visitor;

use comhon\controller\Controller;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\model\ForeignProperty;
use comhon\object\model\MainModel;
use comhon\object\model\ModelArray;
use comhon\object\ObjectCollection;

/**
 * instanciate and populate ObjectCollection
 */
class ObjectCollectionCreator extends Controller {

	private $mLocalObjectCollection;
	
	protected function _getMandatoryParameters() {
		return null;
	}
	
	protected function _isVisitRootObject() {
		return false;
	}
	
	protected function _init($pObject) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('visitor ObjectCollectionCreator must be applied on object with main model');
		}
		$this->mLocalObjectCollection = new ObjectCollection();
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lValue = $pParentObject->getValue($pKey);
		
		// we don't want to visit child object with main model because they can't share LocalObjectCollection
		if ($lValue->getModel() instanceof MainModel) {
			return false;
		}
		if ($lValue->getModel() instanceof ModelArray) {
			return true;
		}
		$lSuccess = $this->mLocalObjectCollection->addObject($lValue, false);
		return true;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {
		return $this->mLocalObjectCollection;
	}
}