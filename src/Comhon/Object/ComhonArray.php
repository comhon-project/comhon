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
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\UnexpectedArrayException;
use Comhon\Model\ModelComplex;
use Comhon\Model\ModelUnique;

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
			if (is_string($model)) {
				$elementModel = ModelManager::getInstance()->getInstanceModel($model);
			} elseif ($model instanceof ModelUnique) {
				$elementModel = $model;
			} else {
				throw new ComhonException('invalid model, ComhonArray must have ModelUnique or ModelArray');
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
	final public function loadValue($key, $propertiesFilter = null, $forceLoad = false) {
		if (!$this->issetValue($key)) {
			throw new ComhonException("cannot load value $key, value not set");
		}
		$value = $this->getValue($key);
		if (!($value instanceof UniqueObject)) {
			throw new ComhonException("cannot load value $key, it is not an unique object");
		}
		return $value->load($propertiesFilter, $forceLoad);
	}
	
	/**
	 * add value at the end of array self::$values
	 * 
	 * @param mixed $value
	 * @param boolean $flagAsUpdated
	 */
	final public function pushValue($value, $flagAsUpdated = true) {
		try {
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedArrayException $e) {
			throw new UnexpectedArrayException($value, $e->getModelArray(), $e->getDepth());
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
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedArrayException $e) {
			throw new UnexpectedArrayException($value, $e->getModelArray(), $e->getDepth());
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
		return $this->_shiftValue($flagAsUpdated);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::setValue()
	 */
	final public function setValue($name, $value, $flagAsUpdated = true) {
		try {
			$this->getModel()->verifElementValue($value);
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction(), $e->getIncrement());
		} catch (UnexpectedArrayException $e) {
			throw new UnexpectedArrayException($value, $e->getModelArray(), $e->getDepth());
		} catch (UnexpectedValueTypeException $e) {
			throw new UnexpectedValueTypeException($value, $e->getExpectedType());
		}
		parent::setValue($name, $value, $flagAsUpdated);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::initValue()
	 */
	final public function initValue($key, $isLoaded = true, $flagAsUpdated = true) {
		$this->setValue($key, $this->getInstanceValue($isLoaded), $flagAsUpdated);
		return $this->getValue($key);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                        Values Getters                                         |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * get instance value
	 *
	 * may only be applied on array that contain a complex model (model instance of \Comhon\Model\ModelComplex)
	 *
	 * @param string $name
	 * @param boolean $isLoaded
	 * @return UniqueObject|ComhonArray
	 */
	final public function getInstanceValue($isLoaded = true) {
		$containedModel = $this->getModel()->getModel();
		if (!($containedModel instanceof ModelComplex)) {
			throw new ComhonException("ComhonArray contain a simple model and can't have instance value");
		}
		return $containedModel->getObjectInstance($isLoaded);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                      ComhonArray Status                                      |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * verify if a value may be added to comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	public function canAddValue() {
		foreach ($this->getModel()->getArrayRestrictions() as $restriction) {
			if (!$restriction->satisfy($this, 1)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * verify if a value may be removed from comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	public function canRemoveValue() {
		if ($this->count() == 0) {
			return false;
		}
		foreach ($this->getModel()->getArrayRestrictions() as $restriction) {
			if (!$restriction->satisfy($this, -1)) {
				return false;
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
		if (!$this->isFlaggedAsUpdated()) {
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
		return $this->isFlaggedAsUpdated();
	}
	
	/**
	 * verify if at least one id value has been updated among all values
	 * 
	 * @return boolean
	 */
	final public function isIdUpdated() {
		if (!$this->isFlaggedAsUpdated() && $this->getModel()->getModel()->isComplex()) {
			foreach ($this->getValues() as $value) {
				if (($value instanceof AbstractComhonObject) && $value->isIdUpdated()) {
					return true;
				}
			}
		}
		return $this->isFlaggedAsUpdated();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::isUpdatedValue()
	 */
	final public function isUpdatedValue($key) {
		if (!$this->isFlaggedAsUpdated()) {
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
		return $this->isFlaggedAsUpdated();
	}
	
	/**
	 * validate comhon array.
	 * 
	 * validation concern only comhon array restrictions.
	 * throw exception if comhon array is not valid.
	 */
	final public function validate() {
		if (!is_null($restriction = Restriction::getFirstNotSatisifed($this->getModel()->getArrayRestrictions(), $this))) {
			throw new NotSatisfiedRestrictionException($this, $restriction);
		}
	}
	
	/**
	 * verify if comhon array is valid.
	 * 
	 * validation concern only comhon array restrictions.
	 * 
	 * @return boolean
	 */
	final public function isValid() {
		return is_null(Restriction::getFirstNotSatisifed($this->getModel()->getArrayRestrictions(), $this));
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