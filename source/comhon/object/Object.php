<?php
namespace comhon\object;

use comhon\model\singleton\ModelManager;
use comhon\model\property\ForeignProperty;
use comhon\model\Model;
use comhon\model\MainModel;
use comhon\model\ModelContainer;
use comhon\model\ModelEnum;
use comhon\model\ModelArray;
use comhon\model\SimpleModel;
use comhon\object\collection\MainObjectCollection;
use comhon\utils\Utils;
use comhon\model\property\AggregationProperty;
use comhon\exception\CastException;

class Object {

	private $mModel;
	private $mIsLoaded;
	private $mIsUpdated = false;
	private $mUpdatedValues = [];
	private $mValues = [];
	private $mIsCasted = false;
	
	
	/**
	 * 
	 * @param string|Model $pModel can be a model name or an instance of model
	 * @param boolean $lIsLoaded
	 */
	public final function __construct($pModel, $lIsLoaded = true) {
		if ($pModel instanceof Model) {
			$this->mModel = $pModel;
		}else {
			$this->mModel = ModelManager::getInstance()->getInstanceModel($pModel);
		}
		if ($this instanceof ObjectArray) {
			if (!($this->mModel instanceof ModelArray)) {
				throw new \Exception('ObjectArray must have ModelArray');
			}
		} else if (($this->mModel instanceof ModelContainer) || ($this->mModel instanceof SimpleModel)) {
			throw new \Exception('Object cannot have ModelContainer or SimpleModel');
		}
		$this->mIsLoaded = $lIsLoaded;
		
		foreach ($this->mModel->getPropertiesWithDefaultValues() as $lProperty) {
			$this->setValue($lProperty->getName(), $lProperty->getDefaultValue(), false);
		}
		foreach ($this->mModel->getAggregations() as $lProperty) {
			$this->initValue($lProperty->getName(), false, false);
		}
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
		if ($pStrict && !is_null($pValue)) {
			if ($this instanceof ObjectArray) {
				$this->mModel->getModel()->verifValue($pValue);
			} else {
				$this->mModel->getProperty($pName, true)->getModel()->verifValue($pValue);
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
	
	public final function deleteValue($pName) {
		if ($this->hasValue($pName)) {
			if ($this->mModel->hasIdProperty($pName) && ($this->mModel instanceof MainModel)) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			unset($this->mValues[$pName]);
			$this->mIsUpdated = true;
			$this->mUpdatedValues[$pName] = true;
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
			if(is_null($this->getValue($lPropertyName)) || $this->getValue($lPropertyName) == '') {
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
		$this->mIsUpdated = false;
		$this->mUpdatedValues = [];
		if ($pRecursive) {
			foreach ($this->mModel->getComplexProperties() as $lPropertyName => $lProperty) {
				if (!$lProperty->isForeign()) {
					if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof Object)) {
						$this->getValue($lPropertyName)->resetUpdatedStatus();
					}
				} else if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof ObjectArray)) {
					$this->getValue($lPropertyName)->resetUpdatedStatus(false);
				}
			}
		}
		foreach ($this->mModel->getDateTimeProperties() as $lPropertyName => $lProperty) {
			if ($this->hasValue($lPropertyName) && ($this->getValue($lPropertyName) instanceof ComhonDateTime)) {
				$this->getValue($lPropertyName)->resetUpdatedStatus();
			}
		}
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
	
	public final function isLoaded() {
		return $this->mIsLoaded;
	}
	
	public final function setLoadStatus() {
		$this->mIsLoaded = true;
	}
	
	public final function setUnLoadStatus() {
		$this->mIsLoaded = false;
	}
	
	public final function isCasted() {
		return $this->mIsCasted;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                      Model - Proeprties                                       |
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
		if (!$pModel->isInheritedFrom($this->mModel)) {
			throw new CastException($pModel, $this->mModel);
		}
		$lhasCompleteId = $this->hasCompleteId();
		if ($lhasCompleteId) {
			if (MainObjectCollection::getInstance()->hasObject($this->getId(), $pModel->getName(), false)) {
				throw new \Exception("Cannot cast object to '{$pModel->getName()}'. Object with id '{$this->getId()}' and model '{$pModel->getName()}' already exists in MainModelCollection");
			}
		}
		$this->mModel = $pModel;
		$this->mIsCasted = true;
		if($this->mModel instanceof MainModel) {
			foreach ($this->mModel->getAggregations() as $lProperty) {
				if (!array_key_exists($lProperty->getName(), $this->mValues)) {
					$this->initValue($lProperty->getName(), false, false);
				}
			}
			if ($lhasCompleteId) {
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
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pName, $pPropertiesFilter = null) {
		$lProperty = $this->getProperty($pName, true);
		if ($lProperty instanceof AggregationProperty) {
			return $lProperty->loadValue($this->getValue($pName), $this, $pPropertiesFilter);
		} else {
			return $lProperty->loadValue($this->getValue($pName), $pPropertiesFilter);
		}
	}
	
	/**
	 * load aggregation by retrieving only ids
	 * @param string $pName
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public final function loadValueIds($pName) {
		return $this->getProperty($pName, true)->loadValueIds($this->getValue($pName), $this);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                           Php Object                                          |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSerializedStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, true, true, $pTimeZone, $pUpdateLoadStatus, false);
	}
	
	public final function fromPublicStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, false, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromPrivateStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, true, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromStdObject($pStdObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->mModel->fillObjectFromStdObject($this, $pStdObject, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	public final function toSerialStdObject($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(true, true, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPublicStdObject($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(false, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPrivateStdObject($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(true, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toStdObject($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->mModel->toStdObject($this, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                              XML                                              |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSerializedXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, true, true, $pTimeZone, $pUpdateLoadStatus, false);
	}
	
	public final function fromPublicXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, false, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromPrivateXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, true, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromXml($pXml, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->mModel->fillObjectFromXml($this, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	public final function toSerialXml($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toXml(true, true, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPublicXml($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toXml(false, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPrivateXml($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toXml(true, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toXml($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getName()}/>");
		$this->mModel->toXml($this, $lXmlNode, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
		return $lXmlNode;
	}

	
	/***********************************************************************************************\
	|                                                                                               |
	|                                sql database - flattened array                                 |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSqlDatabase($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, true, true, $pTimeZone, $pUpdateLoadStatus, false);
	}
	
	public final function fromPublicFlattenedArray($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, false, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromPrivateFlattenedArray($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, true, false, $pTimeZone, $pUpdateLoadStatus, true);
	}
	
	public final function fromFlattenedArray($pRow, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->mModel->fillObjectFromFlattenedArray($this, $pRow, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	public final function toSqlDatabase($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(true, true, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPublicFlattenedArray($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(false, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toPrivateFlattenedArray($pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(true, false, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
	public final function toFlattenedArray($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		return $this->mModel->toFlattenedArray($this, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdatedValueOnly, $pPropertiesFilter, $pMainForeignObjects);
	}
	
}
