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
	private $restriction;
	
	public function __construct($model, Restriction $restriction, $elementName) {
		parent::__construct($model, $elementName);
		$this->restriction = $restriction;
		
		if (!($this->model instanceof SimpleModel)) {
			throw new \Exception('ModelRestrictedArray can only contain SimpleModel, '.get_class($this->model).' given');
		}
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @return ComhonObject
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		$objectArray = parent::_import($interfacedObject, $interfacer, $localObjectCollection, $parentMainModel, $isFirstLevel);
		foreach ($objectArray->getValues() as $value) {
			if (!$this->restriction->satisfy($value)) {
				throw new NotSatisfiedRestrictionException($value, $this->restriction);
			}
		}
		return $objectArray;
	}
	
	public function verifValue($value) {
		if (
			!($value instanceof ObjectArray) 
			|| (
				$value->getModel() !== $this 
				&& $value->getModel()->getModel() !== $this->getModel() 
			)
			|| !($value->getModel() instanceof ModelRestrictedArray)
			|| !$this->restriction->isEqual($value->getModel()->restriction)
		) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be an instance of {$this->getObjectClass()}, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
	/**
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifElementValue($value) {
		parent::verifElementValue($value);
		if (!$this->restriction->satisfy($value)) {
			throw new NotSatisfiedRestrictionException($value, $this->restriction);
		}
		return true;
	}
	
}