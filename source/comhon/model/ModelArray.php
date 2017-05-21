<?php
namespace comhon\model;

use comhon\object\ObjectArray;
use comhon\model\MainModel;
use comhon\object\Object;
use comhon\interfacer\Interfacer;
use comhon\object\collection\ObjectCollection;

class ModelArray extends ModelContainer {
	
	/**
	 * name of each element
	 * for exemple if we have a ModelArray 'children', each element name would be 'child'
	 * @var string
	 */
	private $mElementName;
	
	public function __construct($pModel, $pElementName) {
		parent::__construct($pModel);
		$this->mElementName = $pElementName;
	}
	
	public function getElementName() {
		return $this->mElementName;
	}
	
	public function getObjectClass() {
		return 'comhon\object\ObjectArray';
	}
	
	public function getObjectInstance($pIsloaded = true) {
		return new ObjectArray($this, $pIsloaded);
	}
	
	/**
	 *
	 * @param ObjectArray $pObjectArray
	 * @param Interfacer $pInterfacer
	 */
	protected function _addMainCurrentObject(Object $pObjectArray, Interfacer $pInterfacer) {
		if (!($pObjectArray instanceof ObjectArray)) {
			throw new \Exception('first parameter should be ObjectArray');
		}
		if ($pInterfacer->hasToExportMainForeignObjects()) {
			foreach ($pObjectArray->getValues() as $lObject) {
				if (!is_null($lObject) && ($lObject->getModel() instanceof MainModel) && !is_null($lObject->getId()) && $lObject->hasCompleteId()) {
					$pInterfacer->addMainForeignObject($pInterfacer->createNode('empty'), $lObject->getId(), $lObject->getModel());
				}
			}
		}
	}
	
	/**
	 *
	 * @param ObjectArray $pObjectArray
	 * @param Interfacer $pInterfacer
	 */
	protected function _removeMainCurrentObject(Object $pObjectArray, Interfacer $pInterfacer) {
		if (!($pObjectArray instanceof ObjectArray)) {
			throw new \Exception('first parameter should be ObjectArray');
		}
		if ($pInterfacer->hasToExportMainForeignObjects()) {
			foreach ($pObjectArray->getValues() as $lObject) {
				if (!is_null($lObject) && ($lObject->getModel() instanceof MainModel) && !is_null($lObject->getId()) && $lObject->hasCompleteId()) {
					$pInterfacer->removeMainForeignObject($lObject->getId(), $lObject->getModel());
				}
			}
		}
	}
	
	/**
	 *
	 * @param Object $pObjectArray
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @param boolean $pIsFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($pObjectArray, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		if (is_null($pObjectArray)) {
			return null;
		}
		$this->verifValue($pObjectArray);
		if (!$pObjectArray->isLoaded()) {
			return  Interfacer::__UNLOAD__;
		}
		$lNodeArray = $pInterfacer->createNodeArray($pNodeName);
		
		foreach ($pObjectArray->getValues() as $lValue) {
			$this->verifElementValue($lValue);
			$pInterfacer->addValue($lNodeArray, $this->getModel()->_export($lValue, $this->mElementName, $pInterfacer, $pIsFirstLevel), $this->mElementName);
		}
		return $lNodeArray;
	}
	
	/**
	 *
	 * @param ObjectArray $pObjectArray
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _exportId(Object $pObjectArray, $pNodeName, Interfacer $pInterfacer) {
		$this->verifValue($pObjectArray);
		if (!$pObjectArray->isLoaded()) {
			return  Interfacer::__UNLOAD__;
		}
		$lNodeArray = $pInterfacer->createNodeArray($pNodeName);
		foreach ($pObjectArray->getValues() as $lValue) {
			if (is_null($lValue)) {
				$pInterfacer->addValue($lNodeArray, null, $this->mElementName);
			} else {
				$this->verifElementValue($lValue);
				$pInterfacer->addValue($lNodeArray, $this->getModel()->_exportId($lValue, $this->mElementName, $pInterfacer), $this->mElementName);
			}
		}
		return $lNodeArray;
	}
	
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param MainModel $pParentMainModel
	 * @param boolean $pIsFirstLevel
	 * @return Object
	 */
	protected function _import($pInterfacedObject, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		if ($pInterfacer->isNullValue($pInterfacedObject)) {
			return null;
		}
		if (!$pInterfacer->isArrayNodeValue($pInterfacedObject)) {
			throw new \Exception('unexpeted value type');
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pInterfacer->getTraversableNode($pInterfacedObject) as $lElement) {
			$lObjectArray->pushValue($this->getModel()->_import($lElement, $pInterfacer, $pLocalObjectCollection, $pParentMainModel, $pIsFirstLevel), $pInterfacer->hasToFlagValuesAsUpdated());
		}
		return $lObjectArray;
	}
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param MainModel $pParentMainModel
	 * @return Object
	 */
	protected function _importId($pInterfacedObject, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel) {
		if (is_null($pInterfacedObject)) {
			return null;
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pInterfacer->getTraversableNode($pInterfacedObject) as $lElement) {
			$lObjectArray->pushValue($this->getModel()->_importId($lElement, $pInterfacer, $pLocalObjectCollection, $pParentMainModel), $pInterfacer->hasToFlagValuesAsUpdated());
		}
		return $lObjectArray;
	}
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return ObjectArray
	 */
	public function import($pInterfacedObject, Interfacer $pInterfacer) {
		$this->load();
		if (is_null($pInterfacedObject)) {
			return null;
		}
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pInterfacer->getTraversableNode($pInterfacedObject) as $lElement) {
			$lObjectArray->pushValue($this->getModel()->import($lElement, $pInterfacer), $pInterfacer->hasToFlagValuesAsUpdated());
		}
		return $lObjectArray;
	}
	
	/**
	 *
	 * @param Object $pObjectArray
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 */
	public function fillObject(Object $pObjectArray, $pInterfacedObject, Interfacer $pInterfacer) {
		$this->load();
		$this->verifValue($pObjectArray);
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$lLocalObjectCollection = new ObjectCollection();
		if ($pInterfacer->getMergeType() !== Interfacer::NO_MERGE) {
			foreach ($pObjectArray->getValues() as $lValue) {
				$lLocalObjectCollection->addObject($lValue);
			}
		}
		$pObjectArray->reset();
		foreach ($pInterfacer->getTraversableNode($pInterfacedObject) as $lElement) {
			$pObjectArray->pushValue($this->getModel()->_importMain($lElement, $pInterfacer, $lLocalObjectCollection), $pInterfacer->hasToFlagValuesAsUpdated());
		}
		$pObjectArray->setIsLoaded(true);
	}
	
	public function verifValue($pValue) {
		if (!($pValue instanceof ObjectArray) || ($pValue->getModel()->getModel() !== $this->getModel() && !$pValue->getModel()->getModel()->isInheritedFrom($this->getModel()))) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument passed to {$lNodes[0]['class']}::{$lNodes[0]['function']}() must be an instance of {$this->getObjectClass()}, instance of $lClass given, called in {$lNodes[0]['file']} on line {$lNodes[0]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
	
	/**
	 * 
	 * @param mixed $pValue
	 * @return boolean
	 */
	public function verifElementValue($pValue) {
		return is_null($pValue) ? true : $this->getModel()->verifValue($pValue);
	}
	
}