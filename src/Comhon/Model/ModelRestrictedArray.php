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

use Comhon\Object\ComhonArray;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Value\UnexpectedRestrictedArrayException;

class ModelRestrictedArray extends ModelArray {
	
	/** @var Restriction */
	private $restriction;
	
	/**
	 * 
	 * @param ModelUnique $model
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 * @param boolean $isAssociative
	 * @param string $elementName
	 * @throws \Exception
	 */
	public function __construct(ModelUnique $model, Restriction $restriction, $isAssociative, $elementName) {
		parent::__construct($model, $isAssociative, $elementName);
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
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		$objectArray = parent::_import($interfacedObject, $interfacer, $localObjectCollection, $isFirstLevel);
		if (!is_null($objectArray)) {
			foreach ($objectArray->getValues() as $value) {
				if (!$this->restriction->satisfy($value)) {
					throw new NotSatisfiedRestrictionException($value, $this->restriction);
				}
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
			!($value instanceof ComhonArray) 
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