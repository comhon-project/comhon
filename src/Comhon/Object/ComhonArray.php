<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object;

use Comhon\Model\ModelDateTime;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelContainer;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Model\AbstractModel;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\UnexpectedRestrictedArrayException;

final class ComhonArray extends AbstractComhonObject implements \Iterator {
	
	/**
	 *
	 * @param string|\Comhon\Model\ModelComhonObject|\Comhon\Model\SimpleModel $model can be a model name or an instance of model
	 * @param boolean $isLoaded
	 * @param string $elementName
	 * @param boolean $isAssociative not used if first parameter is instance of ModelArray
	 */
	final public function __construct($model, $isLoaded = true, $elementName = null, $isAssociative = false) {
		if ($model instanceof ModelArray) {
			$objectModel = $model;
		} else {
			$elementModel = ($model instanceof AbstractModel) ? $model : ModelManager::getInstance()->getInstanceModel($model);
		
			if ($elementModel instanceof ModelContainer) {
				throw new ComhonException('ComhonArray cannot have ModelContainer except ModelArray');
			}
			$objectModel = new ModelArray($elementModel, $isAssociative, is_null($elementName) ? $elementModel->getShortName() : $elementName);
		}
		$this->_affectModel($objectModel);
		$this->setIsLoaded($isLoaded);
	}
	
	/**
	 * get unique contained model
	 *
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getUniqueModel() {
		return $this->getModel()->getUniqueModel();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::reset()
	 */
	final public function reset() {
		$this->_reset();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::loadValue()
	 */
	final public function loadValue($pkey, $propertiesFilter = null, $forceLoad = false) {
		return $this->getModel()->getUniqueModel()->loadAndFillObject($this->getValue($pkey), $propertiesFilter, $forceLoad);
	}
	
	/**
	 * add value at the end of array self::$values
	 * 
	 * @param mixed $value
	 * @param boolean $flagAsUpdated
	 */
	final public function pushValue($value, $flagAsUpdated = true) {
		try {
			if ($this->isLoaded()) {
				$this->_verifAddValue();
			}
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedRestrictedArrayException $e) {
			throw new UnexpectedRestrictedArrayException($value, $e->getModelArray());
		} catch (UnexpectedValueTypeException $e) {
			throw new UnexpectedValueTypeException($value, $e->getExpectedType());
		}
		$this->_pushValue($value, $flagAsUpdated);
	}
	
	/**
	 * remove last value from array self::$values
	 *
	 * @param boolean $flagAsUpdated
	 * @return mixed the last value of array. If array is empty,null will be returned.
	 */
	final public function popValue($flagAsUpdated = true) {
		try {
			if ($this->isLoaded()) {
				$this->_verifRemoveValue();
			}
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		}
		return $this->_popValue($flagAsUpdated);
	}
	
	/**
	 * add value at the beginning of array self::$values
	 * 
	 * @param mixed $value
	 * @param boolean $flagAsUpdated
	 */
	final public function unshiftValue($value, $flagAsUpdated = true) {
		try {
			if ($this->isLoaded()) {
				$this->_verifAddValue();
			}
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedRestrictedArrayException $e) {
			throw new UnexpectedRestrictedArrayException($value, $e->getModelArray());
		} catch (UnexpectedValueTypeException $e) {
			throw new UnexpectedValueTypeException($value, $e->getExpectedType());
		}
		$this->_unshiftValue($value, $flagAsUpdated);
	}
	
	/**
	 * remove first value from array self::$values
	 *
	 * @param boolean $flagAsUpdated
	 * @return mixed the first value of array. If array is empty,null will be returned.
	 */
	final public function shiftValue($flagAsUpdated = true) {
		try {
			if ($this->isLoaded()) {
				$this->_verifRemoveValue();
			}
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		}
		return $this->_shiftValue($flagAsUpdated);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::setValue()
	 */
	final public function setValue($name, $value, $flagAsUpdated = true) {
		try {
			if ($this->isLoaded() && !$this->hasValue($name)) {
				$this->_verifAddValue();
			}
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedRestrictedArrayException $e) {
			throw new UnexpectedRestrictedArrayException($value, $e->getModelArray());
		} catch (UnexpectedValueTypeException $e) {
			throw new UnexpectedValueTypeException($value, $e->getExpectedType());
		}
		parent::setValue($name, $value, $flagAsUpdated);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::unsetValue()
	 */
	final public function unsetValue($name, $flagAsUpdated = true) {
		try {
			if ($this->isLoaded() && $this->hasValue($name)) {
				$this->_verifRemoveValue();
			}
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		}
		parent::unsetValue($name, $flagAsUpdated);
	}
	
	/**
	 * verify if a value may be added to given comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	private function _verifAddValue() {
		foreach ($this->getModel()->getArrayRestrictions() as $restriction) {
			if (!$restriction->satisfy($this, 1)) {
				throw new NotSatisfiedRestrictionException($this, $restriction, 1);
			}
		}
		return true;
	}
	
	/**
	 * verify if a value may be removed from given comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	private function _verifRemoveValue() {
		foreach ($this->getModel()->getArrayRestrictions() as $restriction) {
			if (!$restriction->satisfy($this, $this->count() == 0 ? 0 : -1)) {
				throw new NotSatisfiedRestrictionException($this, $restriction, $this->count() == 0 ? 0 : -1);
			}
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::resetUpdatedStatus()
	 */
	final public function resetUpdatedStatus($recursive = true) {
		if ($recursive) {
			$objectHashMap = [];
			$this->_resetUpdatedStatusRecursive($objectHashMap);
		}else {
			$this->_resetUpdatedStatus();
			if ($this->getModel()->getModel() instanceof ModelDateTime) {
				foreach ($this->getValues() as $value) {
					if ($value instanceof ComhonDateTime) {
						$value->resetUpdatedStatus(false);
					}
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::_resetUpdatedStatusRecursive()
	 */
	final protected function _resetUpdatedStatusRecursive(&$objectHashMap) {
		$this->_resetUpdatedStatus();
		if ($this->getModel()->getModel() instanceof ModelDateTime) {
			foreach ($this->getValues() as $value) {
				if ($value instanceof ComhonDateTime) {
					$value->resetUpdatedStatus(false);
				}
			}
		}
		else if ($this->getModel()->getModel()->isComplex()) {
			foreach ($this->getValues() as $value) {
				if ($value instanceof AbstractComhonObject) {
					$value->_resetUpdatedStatusRecursive($objectHashMap);
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::isUpdated()
	 */
	final public function isUpdated() {
		if (!$this->isFlagedAsUpdated()) {
			if ($this->getModel()->getModel()->isComplex()) {
				foreach ($this->getValues() as $value) {
					if (($value instanceof AbstractComhonObject) && $value->isUpdated()) {
						return true;
					}
				}
			}
			else if ($this->getModel()->getModel() instanceof ModelDateTime) {
				foreach ($this->getValues() as $value) {
					if (($value instanceof ComhonDateTime) && $value->isUpdated()) {
						return true;
					}
				}
			}
		}
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 * verify if at least one id value has been updated among all values
	 * 
	 * @return boolean
	 */
	final public function isIdUpdated() {
		if (!$this->isFlagedAsUpdated() && $this->getModel()->getModel()->isComplex()) {
			foreach ($this->getValues() as $value) {
				if (($value instanceof AbstractComhonObject) && $value->isIdUpdated()) {
					return true;
				}
			}
		}
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::isUpdatedValue()
	 */
	final public function isUpdatedValue($key) {
		if (!$this->isFlagedAsUpdated()) {
			if ($this->getModel()->getModel()->isComplex()) {
				$value = $this->getValue($key);
				if (($value instanceof AbstractComhonObject) && $value->isUpdated()) {
					return true;
				}
			}
			else if ($this->getModel()->getModel() instanceof ModelDateTime) {
				$value = $this->getValue($key);
				if (($value instanceof ComhonDateTime) && $value->isUpdated()) {
					return true;
				}
			}
		}
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::validate()
	 */
	final public function validate() {
		if (!$this->isLoaded() && !is_null($restriction = Restriction::getFirstNotSatisifed($this->getModel()->getArrayRestrictions(), $this))) {
			throw new NotSatisfiedRestrictionException($this, $restriction);
		}
	}
	
	/**
	 * get count of element in array self::values
	 * 
	 * @return number
	 */
	final public function count() {
		return count($this->getValues());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::getComhonClass()
	 */
	final public function getComhonClass() {
		return get_class($this) . "({$this->getModel()->getUniqueModel()->getName()})";
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                      Model - Properties                                       |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::_hasToUpdateMainObjectCollection()
	 */
	protected function _hasToUpdateMainObjectCollection($propertyName) {
		return false;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                     Iterator functions                                        |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	 /**
	  * Set the internal pointer of self::$values to its first element
	  */
	final public function rewind() {
		$this->_rewind();
	}
	
	/**
	 * Return the current element in self::$values
	 * 
	 * @return mixed
	 */
	final public function current() {
		return $this->_current();
	}
	
	/**
	 * Fetch a key from self::$values
	 * 
	 * @return mixed
	 */
	final public function key() {
		return $this->_key();
	}
	
	/**
	 * Advance the internal array pointer of self::$values
	 */
	final public function next() {
		$this->_next();
	}
	
	/**
	 * verify if current internal array pointer of self::$values is valid
	 * 
	 * @return boolean
	 */
	final public function valid() {
		return $this->_valid();
	}
	
}