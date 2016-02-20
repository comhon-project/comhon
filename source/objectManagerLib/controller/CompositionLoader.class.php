<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\ObjectCollection;
use objectManagerLib\visitor\ObjectCollectionPopulator;

class CompositionLoader extends Controller {

	const LOAD_CHILDREN = 'loadChildren';

	private $mLoadChildren        = false;
	private $mLoadedCompositions = array();
	private $mObjectCollection;
	private $mObjectCollectionPopulator;
	private $mForeignObjectReplacer;
	
	protected function _getMandatoryParameters() {
		return array();
	}
	
	protected function _init($pObject) {
		$this->mObjectCollection          = ObjectCollection::getInstance();
		$this->mObjectCollectionPopulator = new ObjectCollectionPopulator();
		$this->mForeignObjectReplacer     = new ForeignObjectReplacer();
		
		if (array_key_exists(self::LOAD_CHILDREN, $this->mParams)) {
			$this->mLoadChildren = $this->mParams[self::LOAD_CHILDREN];
		}
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lVisitChildren = true;
		$lObject = $pParentObject->getValue($pKey);
		$lSerializationUnit = $lObject->getModel()->getSerialization();
		if (!is_null($lSerializationUnit) && !is_null($lObject) && ($lObject instanceof ObjectArray) && !is_null($pParentObject)) {
			if ($lSerializationUnit->isComposition($pParentObject->getModel(), $pParentObject->getProperty($pKey)->getSerializationName())) {
				if (!$lObject->isLoaded()) {
					if ($this->mLoadChildren) {
						$lObject = $pParentObject->loadValue($pKey);
					} else {
						$lObject = $pParentObject->loadValueIds($pKey);
					}
					
					$lModel         = ($lObject->getModel() instanceof ModelArray) ? $lObject->getModel()->getModel() : $lObject->getModel();
					$lSerialization = $lModel->getSerialization();
					$lSameSerial    = !is_null($lSerialization) && (spl_object_hash($lSerializationUnit) == spl_object_hash($lSerialization));
					$lObjectToVisit = $lSameSerial ? $lObject : $pParentObject;

					$this->mObjectCollectionPopulator->execute($lObjectToVisit);
					$this->mForeignObjectReplacer->execute($lObjectToVisit);
					$this->mLoadedCompositions[spl_object_hash($lObject)] = null;
				}
				$lVisitChildren = !array_key_exists(spl_object_hash($lObject), $this->mLoadedCompositions);
			}
		}
		return $lVisitChildren;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {}
	
}