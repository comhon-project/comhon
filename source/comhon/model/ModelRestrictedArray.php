<?php
namespace comhon\model;

use comhon\object\ObjectArray;
use comhon\model\MainModel;
use comhon\object\Object;
use comhon\interfacer\Interfacer;
use comhon\object\collection\ObjectCollection;
use comhon\model\restriction\Restriction;
use comhon\exception\NotSatisfiedRestrictionException;

class ModelRestrictedArray extends ModelArray {
	
	/** @var Restriction */
	private $mRestriction;
	
	public function __construct($pModel, Restriction $pRestriction, $pElementName) {
		parent::__construct($pModel, $pElementName);
		$this->mRestriction = $pRestriction;
		
		if (!($this->mModel instanceof SimpleModel)) {
			throw new \Exception('ModelRestrictedArray can only contain SimpleModel, '.get_class($this->mModel).' given');
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
		if (!$pObjectArray->isLoaded()) {
			return  Interfacer::__UNLOAD__;
		}
		$lNodeArray = $pInterfacer->createNodeArray($pNodeName);
		
		foreach ($pObjectArray->getValues() as $lValue) {
			if ($this->mRestriction->satisfy($lValue)) {
				$pInterfacer->addValue($lNodeArray, $this->getModel()->_export($lValue, $this->getElementName(), $pInterfacer, $pIsFirstLevel), $this->getElementName());
			} else {
				throw new NotSatisfiedRestrictionException($lValue, $this->mRestriction);
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
		if (is_null($pInterfacedObject)) {
			return null;
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pInterfacer->getTraversableNode($pInterfacedObject) as $lElement) {
			$lValue = $this->getModel()->_import($lElement, $pInterfacer, $pLocalObjectCollection, $pParentMainModel, $pIsFirstLevel);
			if ($this->mRestriction->satisfy($lValue)) {
				$lObjectArray->pushValue($lValue, $pInterfacer->hasToFlagValuesAsUpdated());
			} else {
				throw new NotSatisfiedRestrictionException($lValue, $this->mRestriction);
			}
			
		}
		return $lObjectArray;
	}
	
	public function verifValue($pValue) {
		if (
			!($pValue instanceof ObjectArray) 
			|| (
				$pValue->getModel() !== $this 
				&& $pValue->getModel()->getModel() !== $this->getModel() 
			)
			|| !($pValue->getModel() instanceof ModelRestrictedArray)
			|| !$this->mRestriction->isEqual($pValue->getModel()->mRestriction)
		) {
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
		parent::verifElementValue($pValue);
		if (!$this->mRestriction->satisfy($pValue)) {
			throw new NotSatisfiedRestrictionException($pValue, $this->mRestriction);
		}
		return true;
	}
	
}