<?php
namespace objectManagerLib\visitor;

use objectManagerLib\controller\Controller;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\ObjectCollection;
use objectManagerLib\object\LocalObjectCollection;

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
		$this->mLocalObjectCollection = new LocalObjectCollection();
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