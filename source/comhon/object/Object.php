<?php
namespace comhon\object;

use comhon\model\Model;
use comhon\model\MainModel;
use comhon\object\collection\MainObjectCollection;
use comhon\model\property\AggregationProperty;
use comhon\exception\CastException;
use comhon\object\ObjectArray;
use comhon\interfacer\Interfacer;
use comhon\interfacer\StdObjectInterfacer;

abstract class Object {

	private $mModel;
	private $mValues        = [];
	private $mIsLoaded;
	private $mUpdatedValues = [];
	private $mIsUpdated     = false;
	private $mIsCasted      = false;
	
	final protected function _affectModel(Model $pModel) {
		if (!is_null($this->mModel)) {
			throw new \Exception('object already initialized');
		}
		$this->mModel = $pModel;
	}
	
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Setters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * 
	 * @param string $pName
	 * @param unknown $pValue
	 * @param boolean $pFlagAsUpdated if true, flag value as updated
	 * @param boolean $pStrict if true, verify value type
	 */
	public final function setValue($pName, $pValue, $pFlagAsUpdated = true, $pStrict = true) {
		if ($pStrict) {
			if ($this instanceof ObjectArray) {
				$this->mModel->verifElementValue($pValue);
			} else {
				$lProperty = $this->mModel->getProperty($pName, true);
				$lProperty->isSatisfiable($pValue, true);
				if (!is_null($pValue)) {
					$lProperty->getModel()->verifValue($pValue);
				}
				if ($lProperty->isAggregation()) {
					$pFlagAsUpdated = false;
				}
			}
		}
		if ($this->mModel->hasIdProperty($pName) && ($this->mModel instanceof MainModel)) {
			if ($this->hasCompleteId() && MainObjectCollection::getInstance()->getObject($this->getId(), $this->mModel->getName()) === $this) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			$this->mValues[$pName] = $pValue;
			MainObjectCollection::getInstance()->addObject($this, false);
		} else {
			$this->mValues[$pName] = $pValue;
		}
		if ($pFlagAsUpdated) {
			$this->mUpdatedValues[$pName] = false;
			$this->mIsUpdated = true;
		} else if (array_key_exists($pName, $this->mUpdatedValues)) {
			unset($this->mUpdatedValues[$pName]);
			if (empty($this->mUpdatedValues)) {
				$this->mIsUpdated = false;
			}
		}
	}
	
	protected final function _pushValue($pValue, $pFlagAsUpdated) {
		$this->mValues[] = $pValue;
		if ($pFlagAsUpdated) {
			$this->mIsUpdated = true;
		}
	}
	
	public final function _popValue($pFlagAsUpdated) {
		array_pop($this->mValues);
		if ($pFlagAsUpdated) {
			$this->mIsUpdated = true;
		}
	}
	
	public final function _unshiftValue($pValue, $pFlagAsUpdated) {
		array_unshift($this->mValues, $pValue);
		if ($pFlagAsUpdated) {
			$this->mIsUpdated = true;
		}
	}
	
	public final function _shiftValue($pFlagAsUpdated) {
		array_shift($this->mValues);
		if ($pFlagAsUpdated) {
			$this->mIsUpdated = true;
		}
	}
	
	public final function unsetValue($pName, $pFlagAsUpdated = true) {
		if ($this->hasValue($pName)) {
			if ($this->mModel->hasIdProperty($pName) && ($this->mModel instanceof MainModel)) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			unset($this->mValues[$pName]);
			if ($pFlagAsUpdated) {
				$this->mIsUpdated = true;
				$this->mUpdatedValues[$pName] = true;
			}
		}
	}
	
	/**
	 * instanciate an Object and add it to values
	 * @param unknown $pPropertyName
	 * @param string $pIsLoaded
	 * @param boolean $pFlagAsUpdated if true, flag value as updated
	 * @return Object
	 */
	public final function initValue($pPropertyName, $pIsLoaded = true, $pFlagAsUpdated = true) {
		$this->setValue($pPropertyName, $this->getInstanceValue($pPropertyName, $pIsLoaded), $pFlagAsUpdated);
		return $this->mValues[$pPropertyName];
	}
	
	protected final function _setValues(array $pValues) {
		$this->mValues = $pValues;
		$this->mIsUpdated = true;
	}
	
	/**
	 * reset values and reset update status
	 */
	public final function reset() {
		if ($this->mModel->hasIdProperties() && ($this->mModel instanceof MainModel)) {
			MainObjectCollection::getInstance()->removeObject($this);
		}
		$this->mValues = [];
		$this->mIsUpdated = false;
		$this->mUpdatedValues = [];
		if (!($this instanceof ObjectArray)) {
			foreach ($this->mModel->getPropertiesWithDefaultValues() as $lProperty) {
				$this->setValue($lProperty->getName(), $lProperty->getDefaultValue(), false);
			}
		}
	}
	
	public function setId($pId, $pFlagAsUpdated = true) {
		if ($this->mModel->hasUniqueIdProperty()) {
			$this->setValue($this->mModel->getUniqueIdProperty()->getName(), $pId, $pFlagAsUpdated);
		}
		else {
			$lIdValues = $this->mModel->decodeId($pId);
			if (count($this->mModel->getIdProperties()) !== count($lIdValues)) {
				throw new \Exception('invalid id : '.$pId);
			}
			$i = 0;
			foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
				$this->setValue($lPropertyName, $lIdValues[$i], $pFlagAsUpdated);
				$i++;
			}
		}
	}
	
	/**
	 * reoder values in same order than properties
	 */
	public final function reorderValues() {
		$lValues = [];
		foreach ($this->mModel->getProperties() as $lPropertyName => $lProperty) {
			if (array_key_exists($lPropertyName, $this->mValues)) {
				$lValues[$lPropertyName] = $this->mValues[$lPropertyName];
			}
		}
		$this->mValues = $lValues;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Getters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function getValue($pName) {
		return ($this->hasValue($pName)) ? $this->mValues[$pName] : null;
	}
	
	public final function getInstanceValue($pPropertyName, $pIsLoaded = true) {
		return $this->getProperty($pPropertyName, true)->getModel()->getObjectInstance($pIsLoaded);
	}
	
	public final function getValues() {
		return $this->mValues;
	}
	
	public final function getDeletedValues() {
		$lDeletedValues = [];
		foreach ($this->mUpdatedValues as $lPropertyName => $lDeleted) {
			if ($lDeleted) {
				$lDeletedValues[] = $lPropertyName;
			}
		}
		return $lDeletedValues;
	}
	
	public final function getUpdatedValues() {
		return $this->mUpdatedValues;
	}
	
	public function getId() {
		if ($this->mModel->hasUniqueIdProperty()) {
			return $this->getValue($this->mModel->getUniqueIdProperty()->getName());
		}
		$lValues = [];
		foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
			$lValues[] = $this->getValue($lPropertyName);
		}
		return $this->mModel->encodeId($lValues);
	}
	
	public final function hasCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
			if(is_null($this->getValue($lPropertyName))) {
				return false;
			}
		}
		return true;
	}
	
	public final function verifCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
			if(is_null($this->getValue($lPropertyName)) || $this->getValue($lPropertyName) == '') {
				throw new \Excpetion("id is not complete, property '$lPropertyName' is empty");
			}
		}
	}
	
	public final function hasValue($pName) {
		return array_key_exists($pName, $this->mValues);
	}
	
	public final function hasValues($Names) {
		foreach ($Names as $lName) {
			if (!$this->hasValue($lName)) {
				return false;
			}
		}
		return true;
	}
	
	public final function getValuesCount() {
		return count($this->mValues);
	}
	
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       Iterator functions                                      |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	protected function _rewind() {
		reset($this->mValues);
	}
	
	protected function _current() {
		return current($this->mValues);
	}
	
	protected function _key() {
		return key($this->mValues);
	}
	
	protected function _next() {
		next($this->mValues);
	}
	
	protected function _valid() {
		return key($this->mValues) !== null;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                         Object Status                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isUpdated() {
		if (!$this->mIsUpdated) {
			foreach ($this->getModel()->getComplexProperties() as $lPropertyName => $lProperty) {
				if ($this->isUpdatedValue($lPropertyName)) {
					return true;
				}
			}
			foreach ($this->getModel()->getDateTimeProperties() as $lPropertyName => $lProperty) {
				if ($this->isUpdatedValue($lPropertyName)) {
					return true;
				}
			}
		}
		return $this->mIsUpdated;
	}
	
	/**
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isIdUpdated() {
		foreach ($this->getModel()->getIdProperties() as $lPropertyName => $lProperty) {
			if ($this->isUpdatedValue($lPropertyName)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * verify if a value has been updated
	 * only works for object that have a model insance of MainModel, otherwise false will be return 
	 * @param string $pPropertyName
	 * @return boolean
	 */
	public function isUpdatedValue($pPropertyName) {
		if ($this->hasProperty($pPropertyName)) {
			if (array_key_exists($pPropertyName, $this->mUpdatedValues)) {
				return true;
			} else if ($this->hasValue($pPropertyName)) {
				if ($this->mValues[$pPropertyName] instanceof Object) {
					return $this->getProperty($pPropertyName)->isForeign()
						? $this->mValues[$pPropertyName]->isIdUpdated()
						: $this->mValues[$pPropertyName]->isUpdated();
				}
				else if ($this->mValues[$pPropertyName] instanceof ComhonDateTime) {
					return $this->mValues[$pPropertyName]->isUpdated();
				}
			}
		}
		return false;
	}
	
	/**
	 * verify if object is flaged as updated
	 * do not use this function to known if object is updated (use self::isUpdated)
	 * @return boolean
	 */
	public function isFlagedAsUpdated() {
		return $this->mIsUpdated;
	}
	
	/**
	 * verify if a value is flaged as updated
	 * do not use this function to known if a value is updated (use self::isUpdatedValue)
	 * @param unknown $pPropertyName
	 * @return boolean
	 */
	public function isValueFlagedAsUpdated($pPropertyName) {
		return array_key_exists($pPropertyName, $this->mUpdatedValues);
	}
	
	public function resetUpdatedStatus($pRecursive = true) {
		if ($pRecursive) {
			$lObjectHashMap = [];
			$this->_resetUpdatedStatusRecursive($lObjectHashMap);
		}else {
			$this->mIsUpdated = false;
			$this->mUpdatedValues = [];
			foreach ($this->mModel->getDateTimeProperties() as $lPropertyName => $lProperty) {
				if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof ComhonDateTime)) {
					$this->getValue($lPropertyName)->resetUpdatedStatus(false);
				}
			}
		}
	}
	
	protected function _resetUpdatedStatusRecursive(&$pObjectHashMap) {
		if (array_key_exists(spl_object_hash($this), $pObjectHashMap)) {
			if ($pObjectHashMap[spl_object_hash($this)] > 0) {
				trigger_error('Warning loop detected');
				return;
			}
		} else {
			$pObjectHashMap[spl_object_hash($this)] = 0;
		}
		$pObjectHashMap[spl_object_hash($this)]++;
		$this->mIsUpdated = false;
		$this->mUpdatedValues = [];
		foreach ($this->mModel->getComplexProperties() as $lPropertyName => $lProperty) {
			if (!$lProperty->isForeign()) {
				if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof Object)) {
					$this->getValue($lPropertyName)->_resetUpdatedStatusRecursive($pObjectHashMap);
				}
			} else if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof ObjectArray)) {
				$this->getValue($lPropertyName)->resetUpdatedStatus(false);
			}
		}
		foreach ($this->mModel->getDateTimeProperties() as $lPropertyName => $lProperty) {
			if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof ComhonDateTime)) {
				$this->getValue($lPropertyName)->resetUpdatedStatus(false);
			}
		}
		$pObjectHashMap[spl_object_hash($this)]--;
	}
	
	/**
	 * reset updated Status (reset only self::mIsUpdated and self::mUpdatedValues)
	 */
	protected final function _resetUpdatedStatus() {
		$this->mIsUpdated = false;
		$this->mUpdatedValues = [];
	}
	
	/**
	 * flag value as updated
	 * @param string $pPropertyName
	 * @return boolean true if success
	 */
	public function flagValueAsUpdated($pPropertyName) {
		if ($this->hasValue($pPropertyName)) {
			$this->mIsUpdated = true;
			$this->mUpdatedValues[$pPropertyName] = false;
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public final function isLoaded() {
		return $this->mIsLoaded;
	}
	
	/**
	 * 
	 * @param boolean $pIsLoaded
	 */
	public final function setIsLoaded($pIsLoaded) {
		$this->mIsLoaded = $pIsLoaded;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public final function isCasted() {
		return $this->mIsCasted;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                      Model - Properties                                       |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 *
	 * @return Model
	 */
	public final function getModel() {
		return $this->mModel;
	}
	
	public final function cast(Model $pModel) {
		if ($this instanceof ObjectArray) {
			throw new \Exception('object array cannot be casted');
		}
		if ($this->mModel === $pModel) {
			return;
		}
		if (!$pModel->isInheritedFrom($this->mModel)) {
			throw new CastException($pModel, $this->mModel);
		}
		$lAddObject = false;
		if ($this->hasCompleteId() && $this->getModel()->hasIdProperties()) {
			$lObject = MainObjectCollection::getInstance()->getObject($this->getId(), $pModel->getName());
			if ($lObject === $this) {
				$lAddObject = true;
				if (MainObjectCollection::getInstance()->hasObject($this->getId(), $pModel->getName(), false)) {
					throw new \Exception("Cannot cast object to '{$pModel->getName()}'. Object with id '{$this->getId()}' and model '{$pModel->getName()}' already exists in MainModelCollection");
				}
			}
		}
		$this->mModel = $pModel;
		$this->mIsCasted = true;
		if ($this->mModel instanceof MainModel) {
			foreach ($this->mModel->getAggregations() as $lProperty) {
				if (!array_key_exists($lProperty->getName(), $this->mValues)) {
					$this->initValue($lProperty->getName(), false, false);
				}
			}
			if ($lAddObject) {
				MainObjectCollection::getInstance()->addObject($this);
			}
		}
	}
	
	public final function hasProperty($pPropertyName) {
		return $this->mModel->hasProperty($pPropertyName);
	}
	
	public final function getProperties() {
		return $this->mModel->getProperties();
	}
	
	public final function getPropertiesNames() {
		return $this->mModel->getPropertiesNames();
	}
	
	public final function getProperty($pPropertyName, $pThrowException = false) {
		return $this->mModel->getProperty($pPropertyName, $pThrowException);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                Serialization / Deserialization                                |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 *
	 * @param string $pOperation specify it only if object serialization is sqlDatabase
	 * @throws \Exception
	 * @return integer
	 */
	public final function save($pOperation = null) {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->saveObject($this, $pOperation);
	}
	
	/**
	 *
	 * @throws \Exception
	 * @return integer
	 */
	public final function delete() {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->deleteObject($this);
	}
	
	/**
	 * load value
	 * @param string $pName
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pName, $pPropertiesFilter = null, $pForceLoad = false) {
		$lProperty = $this->getProperty($pName, true);
		if ($lProperty instanceof AggregationProperty) {
			if (!$this->hasValue($pName)) {
				$this->initValue($pName, false);
			}
			return $lProperty->loadAggregationValue($this->getValue($pName), $this, $pPropertiesFilter, $pForceLoad);
		} else {
			return $lProperty->loadValue($this->getValue($pName), $pPropertiesFilter, $pForceLoad);
		}
	}
	
	/**
	 * load aggregation by retrieving only ids
	 * @param string $pName
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public final function loadValueIds($pName, $pForceLoad = false) {
		if (!$this->hasValue($pName)) {
			$this->initValue($pName, false);
		}
		return $this->getProperty($pName, true)->loadValueIds($this->getValue($pName), $this, $pForceLoad);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                       export / import                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * 
	 * @param Interfacer $pInterfacer
	 * @return mixed|null
	 */
	public final function export(Interfacer $pInterfacer) {
		return $this->mModel->export($this, $pInterfacer);
	}
	
	/**
	 * 
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 */
	public final function fill($pInterfacedObject, Interfacer $pInterfacer) {
		$this->mModel->fillObject($this, $pInterfacedObject, $pInterfacer);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       toString / debug                                        |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			$lInterfacer = new StdObjectInterfacer();
			$lInterfacer->setPrivateContext(true);
			return json_encode($lInterfacer->export($this), JSON_PRETTY_PRINT)."\n";
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		return '';
	}
	
	/**
	 *
	 * @return array
	 */
	public function __debugInfo() {
		$lDebugObject = get_object_vars($this);
		if (!array_key_exists('mModel', $lDebugObject)) {
			throw new \Exception('model attribut doesn\'t exist anymore');
		}
		$lDebugObject['mModel'] = $this->mModel->getName();
		return $lDebugObject;
	}
	
}
