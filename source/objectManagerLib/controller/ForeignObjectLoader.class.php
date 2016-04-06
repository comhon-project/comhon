<?php
namespace objectManagerLib\controller;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\ObjectCollection;
use objectManagerLib\visitor\ObjectCollectionPopulator;

class ForeignObjectLoader extends Controller {

	private $mLoadCompositions      = true;
	private $mLoadedValues          = array();
	private $mObjectCollection;
	private $mObjectCollectionPopulator;
	private $mForeignObjectReplacer;
	
	protected function _init($pObject) {
		$this->mObjectCollection          = ObjectCollection::getInstance();
		//$this->mObjectCollectionPopulator = new ObjectCollectionPopulator();
		//$this->mForeignObjectReplacer     = new ForeignObjectReplacer();
		
		if (array_key_exists(0, $this->mParams)) {
			$this->mLoadCompositions = $this->mParams[0];
		}
	}
	
	protected function _getMandatoryParameters() {
		return array();
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lVisitChildren = true;
		$lObject = $pParentObject->getValue($pKey);
		$lSerializationUnit = $lObject->getModel()->getSerialization();
		if (!is_null($lSerializationUnit) && !is_null($lObject) && !is_null($pParentObject)) {
			$lIsComposition = !($pParentObject->getModel() instanceof ModelContainer) && $lSerializationUnit->isComposition($pParentObject->getModel(), $pParentObject->getProperty($pKey)->getSerializationName());
			if (!$lObject->isLoaded() && ($this->mLoadCompositions || !$lIsComposition)) {
				
				$lObject        = $pParentObject->loadValue($pKey);
				$lModel         = ($lObject->getModel() instanceof ModelArray) ? $lObject->getModel()->getModel() : $lObject->getModel();
				$lSerialization = $lModel->getSerialization();
				$lSameSerial    = !is_null($lSerialization) && (spl_object_hash($lSerializationUnit) == spl_object_hash($lSerialization));
				$lObjectToVisit = $lSameSerial ? $lObject : $pParentObject;

				//$this->mObjectCollectionPopulator->execute($lObjectToVisit);
				//$this->mForeignObjectReplacer->execute($lObjectToVisit);
				$this->mLoadedValues[spl_object_hash($lObject)] = null;
			}
			$lVisitChildren = !array_key_exists(spl_object_hash($lObject), $this->mLoadedValues);
		}
		return $lVisitChildren;
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {}
	
}