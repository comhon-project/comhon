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
	private $mSerializationStack; 
	
	protected function _getMandatoryParameters() {
		return null;
	}
	
	protected function _init($pObject) {
		$this->mObjectCollection = ObjectCollection::getInstance();
		$this->mSerializationStack = array(array(0,0));
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack, $pSerializationUnit) {
		$lValue = $pParentObject->getValue($pKey);
		$lSuccess = $this->mObjectCollection->addObject($lValue, $pSerializationUnit);
		$this->mSerializationStack[] = $this->mObjectCollection->getCurrentKey();
		return true;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack, $pSerializationUnit) {
		array_pop($this->mSerializationStack);
		$lIndex = count($this->mSerializationStack) - 1;
		$this->mObjectCollection->getCurrentKey($this->mSerializationStack[$lIndex][0], $this->mSerializationStack[$lIndex][1]);
	}
	
	protected function _finalize($pObject) {
		return $this->mObjectCollection;
	}
}