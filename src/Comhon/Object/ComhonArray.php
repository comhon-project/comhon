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

use Comhon\Model\Model;
use Comhon\Model\ModelDateTime;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelContainer;
use Comhon\Exception\ComhonException;
use Comhon\Exception\NotSatisfiedRestrictionException;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Model\ModelComhonObject;

final class ComhonArray extends AbstractComhonObject implements \Iterator {
	
	/**
	 *
	 * @param string|\Comhon\Model\ModelComhonObject $model can be a model name or an instance of model
	 * @param boolean $isLoaded
	 * @param string $elementName
	 * @param boolean $isAssociative not used if first parameter is instance of ModelArray
	 */
	final public function __construct($model, $isLoaded = true, $elementName = null, $isAssociative = false) {
		if ($model instanceof ModelArray) {
			$objectModel = $model;
		} else {
			$elementModel = ($model instanceof ModelComhonObject) ? $model : ModelManager::getInstance()->getInstanceModel($model);
		
			if ($elementModel instanceof ModelContainer) {
				throw new ComhonException('ComhonObject cannot have ModelContainer except ModelArray');
			}
			$objectModel = new ModelArray($elementModel, $isAssociative, is_null($elementName) ? $elementModel->getShortName() : $elementName);
		}
		$this->setIsLoaded($isLoaded);
		$this->_affectModel($objectModel);
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
	 * set values
	 * 
	 * @param unknown $values
	 * @param string $flagAsUpdated
	 */
	final public function setValues($values, $flagAsUpdated = true) {
		foreach ($values as $value) {
			try {
				$this->getModel()->verifElementValue($value);
			}
			catch (NotSatisfiedRestrictionException $e) {
				throw new NotSatisfiedRestrictionException($value, $e->getRestriction());
			}
			catch (UnexpectedValueTypeException $e) {
				throw new UnexpectedValueTypeException($value, $e->getExpectedType());
			}
		}
		$this->_setValues($values, $flagAsUpdated);
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
		}
		catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($value, $e->getRestriction());
		}
		catch (UnexpectedValueTypeException $e) {
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
		}
		catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($value, $e->getRestriction());
		}
		catch (UnexpectedValueTypeException $e) {
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
	 * @see \Comhon\Object\AbstractComhonObject::_verifyValueBeforeSet()
	 */
	protected function _verifyValueBeforeSet($name, $value, &$flagAsUpdated) {
		$this->getModel()->verifElementValue($value);
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