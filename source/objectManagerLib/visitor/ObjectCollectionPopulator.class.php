<?php
namespace objectManagerLib\visitor;

use objectManagerLib\controller\Controller;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\ObjectCollection;

/**
 * instanciate and populate ObjectCollection
 */
class ObjectCollectionPopulator extends Controller {

	private $mObjectCollection;
	private $mMainModelStack; 
	
	protected function _getMandatoryParameters() {
		return null;
	}
	
	protected function _init($pObject) {
		$this->mObjectCollection = ObjectCollection::getInstance();
		$this->mMainModelStack = array(array(0,0));
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lValue = $pParentObject->getValue($pKey);
		$lSuccess = $this->mObjectCollection->addObject($lValue);
		$this->mMainModelStack[] = $this->mObjectCollection->getCurrentKey();
		return true;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {
		array_pop($this->mMainModelStack);
		$lIndex = count($this->mMainModelStack) - 1;
		$this->mObjectCollection->getCurrentKey($this->mMainModelStack[$lIndex][0], $this->mMainModelStack[$lIndex][1]);
	}
	
	protected function _finalize($pObject) {
		return $this->mObjectCollection;
	}
}