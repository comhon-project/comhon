<?php
namespace comhon\controller;

use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\model\ForeignProperty;
use comhon\object\model\ModelArray;
use comhon\object\ObjectCollection;

class CompositionLoader extends Controller {

	const LOAD_CHILDREN = 'loadChildren';

	private $mLoadChildren        = false;
	private $mLoadedCompositions = array();
	
	protected function _getMandatoryParameters() {
		return array();
	}
	
	protected function _init($pObject) {
		if (array_key_exists(self::LOAD_CHILDREN, $this->mParams)) {
			$this->mLoadChildren = $this->mParams[self::LOAD_CHILDREN];
		}
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lVisitChildren = true;
		$lObject        = $pParentObject->getValue($pKey);
		$lIsComposition = $pParentObject->hasProperty($pKey) && $pParentObject->getProperty($pKey)->isComposition();
		
		if ($lIsComposition && !is_null($lObject) && ($lObject instanceof ObjectArray)) {
			if (!$lObject->isLoaded()) {
				if ($this->mLoadChildren) {
					$pParentObject->loadValue($pKey);
				} else {
					$pParentObject->loadValueIds($pKey);
				}
				$this->mLoadedCompositions[spl_object_hash($lObject)] = null;
			}
			$lVisitChildren = !array_key_exists(spl_object_hash($lObject), $this->mLoadedCompositions);
		}
		
		return $lVisitChildren;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {
		$this->mLoadedCompositions = array();
	}
	
}