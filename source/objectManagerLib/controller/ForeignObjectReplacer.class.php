<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\ObjectCollection;

class ForeignObjectReplacer extends Controller {
	
	private $mObjectCollection;
	private $mMainModelStack; 
	
	protected function _getMandatoryParameters() {
		return array();
	}
	
	protected function _init($pObject) {
		$this->mObjectCollection = ObjectCollection::getInstance();
		$this->mMainModelStack = array(array(0,0));
		var_dump(ObjectCollection::getInstance()->toString());
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lSuccess = $this->mObjectCollection->replaceValue($pParentObject, $pKey);
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