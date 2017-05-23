<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Object\ObjectArray;
use Comhon\Model\MainModel;
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\NotSatisfiedRestrictionException;

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
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param MainModel $pParentMainModel
	 * @param boolean $pIsFirstLevel
	 * @return ComhonObject
	 */
	protected function _import($pInterfacedObject, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		$lObjectArray = parent::_import($pInterfacedObject, $pInterfacer, $pLocalObjectCollection, $pParentMainModel, $pIsFirstLevel);
		foreach ($lObjectArray->getValues() as $lValue) {
			if (!$this->mRestriction->satisfy($lValue)) {
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