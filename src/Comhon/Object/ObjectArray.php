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

use Comhon\Model\MainModel;
use Comhon\Model\Model;
use Comhon\Model\ModelDateTime;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelArray;

class ObjectArray extends ComhonObject implements \Iterator {

	/**
	 *
	 * @param string|Model $model can be a model name or an instance of model
	 * @param boolean $isLoaded
	 */
	final public function __construct($model, $isLoaded = true, $elementName = null) {
		if ($model instanceof ModelArray) {
			$objectModel = $model;
		} else {
			$elementModel = ($model instanceof Model) ? $model : ModelManager::getInstance()->getInstanceModel($model);
		
			if ($elementModel instanceof ModelContainer) {
				throw new \Exception('Object cannot have ModelContainer except ModelArray');
			}
			$objectModel = new ModelArray($elementModel, is_null($elementName) ? $model->getName() : $elementName);
		}
		$this->setIsLoaded($isLoaded);
		$this->_affectModel($objectModel);
	}
	
	/**
	 *
	 * @param string $name
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pkey, $propertiesFilter = null, $forceLoad = false) {
		return $this->getModel()->getUniqueModel()->loadAndFillObject($this->getValue($pkey), $propertiesFilter, $forceLoad);
	}
	
	public function getId() {
		return null;
	}
	
	public final function setValues($values) {
		$this->_setValues($values);
	}
	
	public final function pushValue($value, $flagAsUpdated = true, $strict = true) {
		if ($strict) {
			$this->getModel()->verifElementValue($value);
		}
		$this->_pushValue($value, $flagAsUpdated);
	}
	
	public final function popValue($flagAsUpdated = true) {
		$this->_popValue($flagAsUpdated);
	}
	
	public final function unshiftValue($value, $flagAsUpdated = true, $strict = true) {
		if ($strict) {
			$this->getModel()->verifElementValue($value);
		}
		$this->_unshiftValue($value, $flagAsUpdated);
	}
	
	public final function shiftValue($flagAsUpdated = true) {
		$this->_shiftValue($flagAsUpdated);
	}
	
	public function resetUpdatedStatus($recursive = true) {
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
	
	protected function _resetUpdatedStatusRecursive(&$objectHashMap) {
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
				if ($value instanceof ComhonObject) {
					$value->_resetUpdatedStatusRecursive($objectHashMap);
				}
			}
		}
	}
	
	/**
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isUpdated() {
		if (!$this->isFlagedAsUpdated()) {
			if ($this->getModel()->getModel()->isComplex()) {
				foreach ($this->getValues() as $value) {
					if (($value instanceof ComhonObject) && $value->isUpdated()) {
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
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isIdUpdated() {
		if (!$this->isFlagedAsUpdated() && $this->getModel()->getModel()->isComplex()) {
			foreach ($this->getValues() as $value) {
				if (($value instanceof ComhonObject) && $value->isIdUpdated()) {
					return true;
				}
			}
		}
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 * verify if a value has been updated
	 * only works for object that have a model insance of MainModel, otherwise false will be return
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isUpdatedValue($key) {
		if (!$this->isFlagedAsUpdated()) {
			if ($this->getModel()->getModel()->isComplex()) {
				$value = $this->getValue($key);
				if (($value instanceof ComhonObject) && $value->isUpdated()) {
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
	
	
	public function count() {
		return count($this->getValues());
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                     Iterator functions                                        |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	
	public function rewind() {
		$this->_rewind();
	}
	
	public function current() {
		return $this->_current();
	}
	
	public function key() {
		return $this->_key();
	}
	
	public function next() {
		$this->_next();
	}
	
	public function valid() {
		return $this->_valid();
	}
	
}