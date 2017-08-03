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
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\NotSatisfiedRestrictionException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Exception\UnexpectedRestrictedArrayException;

class ModelRestrictedArray extends ModelArray {
	
	/** @var Restriction */
	private $restriction;
	
	/**
	 * 
	 * @param Model $model
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 * @param string $elementName
	 * @throws \Exception
	 */
	public function __construct(Model $model, Restriction $restriction, $elementName) {
		parent::__construct($model, $elementName);
		$this->restriction = $restriction;
		
		if (!($this->model instanceof SimpleModel)) {
			throw new ComhonException('ModelRestrictedArray can only contain SimpleModel, '.get_class($this->model).' given');
		}
	}
	
	/**
	 * get stringified restriction
	 * 
	 * @return string
	 */
	public function getStringifiedRestriction() {
		return $this->restriction->toString();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::_import()
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		$objectArray = parent::_import($interfacedObject, $interfacer, $localObjectCollection, $mainModelContainer, $isFirstLevel);
		foreach ($objectArray->getValues() as $value) {
			if (!$this->restriction->satisfy($value)) {
				throw new NotSatisfiedRestrictionException($value, $this->restriction);
			}
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::verifValue()
	 */
	public function verifValue($value) {
		if (
			!($value instanceof ObjectArray) 
			|| (
				$value->getModel() !== $this 
				&& $value->getModel()->getModel() !== $this->getModel() 
			)
		){
			$Obj = $this->getObjectInstance();
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		if ($value->getModel() !== $this 
			&& (
				!($value->getModel() instanceof ModelRestrictedArray) 
				|| !$this->restriction->isEqual($value->getModel()->restriction)
			)
		) {
			throw new UnexpectedRestrictedArrayException($value, $this);
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelArray::verifElementValue()
	 */
	public function verifElementValue($value) {
		parent::verifElementValue($value);
		if (!$this->restriction->satisfy($value)) {
			throw new NotSatisfiedRestrictionException($value, $this->restriction);
		}
		return true;
	}
	
}