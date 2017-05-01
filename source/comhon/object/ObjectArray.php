<?php
namespace comhon\object;

use comhon\model\MainModel;
use comhon\model\Model;
use comhon\model\ModelDateTime;
use comhon\model\singleton\ModelManager;
use comhon\model\ModelArray;

class ObjectArray extends Object {

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
		if ($pStrict && !is_null($pValue)) {
			$this->getModel()->getModel()->verifValue($pValue);
		}
		$this->_pushValue($pValue, $pFlagAsUpdated);
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
				if ($lValue instanceof Object) {
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
					if (($lValue instanceof Object) && $lValue->isUpdated()) {
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
				if (($lValue instanceof Object) && $lValue->isIdUpdated()) {
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
				if (($lValue instanceof Object) && $lValue->isUpdated()) {
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
	
	public function fromSqlDatabaseId($pRows, $pTimeZone = null, $pUpdateLoadStatus = true) {
		if (!($this->getModel()->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->reset();
		foreach ($pRows as $lRow) {
			$this->pushValue($this->getModel()->getModel()->fromSqlDatabaseId($lRow), false);
		}
		if ($pUpdateLoadStatus) {
			$this->setIsLoaded(true);
		}
	}
	
}