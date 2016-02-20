<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\ObjectCollection;

class ForeignObjectReplacer extends Controller {
	
	private $mObjectCollection;
	private $mSerializationStack; 
	
	protected function _getMandatoryParameters() {
		return array();
	}
	
	protected function _init($pObject) {
		$this->mObjectCollection = ObjectCollection::getInstance();
		$this->mSerializationStack = array(array(0,0));
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lSerializationUnit = $pParentObject->getValue($pKey)->getModel()->getSerialization();
		$lSuccess = $this->mObjectCollection->replaceValue($pParentObject, $pKey, $lSerializationUnit);
		$this->mSerializationStack[] = $this->mObjectCollection->getCurrentKey();
		return true;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {
		array_pop($this->mSerializationStack);
		$lIndex = count($this->mSerializationStack) - 1;
		$this->mObjectCollection->getCurrentKey($this->mSerializationStack[$lIndex][0], $this->mSerializationStack[$lIndex][1]);
	}
	
	protected function _finalize($pObject) {
		return $this->mObjectCollection;
	}
	
}