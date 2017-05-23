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
	 * @param string|Model $pModel can be a model name or an instance of model
	 * @param boolean $lIsLoaded
	 */
	final public function __construct($pModel, $pIsLoaded = true, $pElementName = null) {
		if ($pModel instanceof ModelArray) {
			$lModel = $pModel;
		} else {
			$lElementModel = ($pModel instanceof Model) ? $pModel : ModelManager::getInstance()->getInstanceModel($pModel);
		
			if ($lElementModel instanceof ModelContainer) {
				throw new \Exception('Object cannot have ModelContainer except ModelArray');
			}
			$lModel = new ModelArray($lElementModel, is_null($pElementName) ? $pModel->getName() : $pElementName);
		}
		$this->setIsLoaded($pIsLoaded);
		$this->_affectModel($lModel);
	}
	
	/**
	 *
	 * @param string $pName
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pkey, $pPropertiesFilter = null, $pForceLoad = false) {
		return $this->getModel()->getUniqueModel()->loadAndFillObject($this->getValue($pkey), $pPropertiesFilter, $pForceLoad);
	}
	
	public function getId() {
		return null;
	}
	
	public final function setValues($pValues) {
		$this->_setValues($pValues);
	}
	
	public final function pushValue($pValue, $pFlagAsUpdated = true, $pStrict = true) {
		if ($pStrict) {
			$this->getModel()->verifElementValue($pValue);
		}
		$this->_pushValue($pValue, $pFlagAsUpdated);
	}
	
	public final function popValue($pFlagAsUpdated = true) {
		$this->_popValue($pFlagAsUpdated);
	}
	
	public final function unshiftValue($pValue, $pFlagAsUpdated = true, $pStrict = true) {
		if ($pStrict) {
			$this->getModel()->verifElementValue($pValue);
		}
		$this->_unshiftValue($pValue, $pFlagAsUpdated);
	}
	
	public final function shiftValue($pFlagAsUpdated = true) {
		$this->_shiftValue($pFlagAsUpdated);
	}
	
	public function resetUpdatedStatus($pRecursive = true) {
		if ($pRecursive) {
			$lObjectHashMap = [];
			$this->_resetUpdatedStatusRecursive($lObjectHashMap);
		}else {
			$this->_resetUpdatedStatus();
			if ($this->getModel()->getModel() instanceof ModelDateTime) {
				foreach ($this->getValues() as $lValue) {
					if ($lValue instanceof ComhonDateTime) {
						$lValue->resetUpdatedStatus(false);
					}
				}
			}
		}
	}
	
	protected function _resetUpdatedStatusRecursive(&$pObjectHashMap) {
		$this->_resetUpdatedStatus();
		if ($this->getModel()->getModel() instanceof ModelDateTime) {
			foreach ($this->getValues() as $lValue) {
				if ($lValue instanceof ComhonDateTime) {
					$lValue->resetUpdatedStatus(false);
				}
			}
		}
		else if ($this->getModel()->getModel()->isComplex()) {
			foreach ($this->getValues() as $lValue) {
				if ($lValue instanceof ComhonObject) {
					$lValue->_resetUpdatedStatusRecursive($pObjectHashMap);
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
				foreach ($this->getValues() as $lValue) {
					if (($lValue instanceof ComhonObject) && $lValue->isUpdated()) {
						return true;
					}
				}
			}
			else if ($this->getModel()->getModel() instanceof ModelDateTime) {
				foreach ($this->getValues() as $lValue) {
					if (($lValue instanceof ComhonDateTime) && $lValue->isUpdated()) {
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
			foreach ($this->getValues() as $lValue) {
				if (($lValue instanceof ComhonObject) && $lValue->isIdUpdated()) {
					return true;
				}
			}
		}
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 * verify if a value has been updated
	 * only works for object that have a model insance of MainModel, otherwise false will be return
	 * @param string $pPropertyName
	 * @return boolean
	 */
	public function isUpdatedValue($pKey) {
		if (!$this->isFlagedAsUpdated()) {
			if ($this->getModel()->getModel()->isComplex()) {
				$lValue = $this->getValue($pKey);
				if (($lValue instanceof ComhonObject) && $lValue->isUpdated()) {
					return true;
				}
			}
			else if ($this->getModel()->getModel() instanceof ModelDateTime) {
				$lValue = $this->getValue($pKey);
				if (($lValue instanceof ComhonDateTime) && $lValue->isUpdated()) {
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