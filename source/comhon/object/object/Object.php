<?php
namespace comhon\object\object;

use comhon\object\singleton\InstanceModel;
use comhon\object\model\ForeignProperty;
use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\model\ModelContainer;
use comhon\object\model\ModelEnum;
use comhon\object\model\ModelArray;
use comhon\object\model\SimpleModel;
use comhon\object\MainObjectCollection;

class Object {

	private $mModel;
	private $mIsLoaded;
	private $mValues = [];
	private $mIdValues = [];
	
	
	/**
	 * 
	 * @param string|Model $pModel can be a model name or an instance of model
	 * @param boolean $lIsLoaded
	 */
	public final function __construct($pModel, $lIsLoaded = true) {
		if (is_object($pModel) && ($pModel instanceof Model)) {
			$this->mModel = $pModel;
		}else {
			$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModel);
		}
		if (($this instanceof ObjectArray) && !($this->mModel instanceof ModelArray)) {
			throw new \Exception('ObjectArray must have ModelArray');
		}
		$this->mIsLoaded = $lIsLoaded;
		
		foreach ($this->mModel->getPropertiesWithDefaultValues() as $lProperty) {
			$this->setValue($lProperty->getName(), $lProperty->getDefaultValue());
		}
		foreach ($this->mModel->getCompositions() as $lProperty) {
			$this->initValue($lProperty->getName(), false);
		}
	}
	
	/**
	 * 
	 * @return Model
	 */
	public final function getModel() {
		return $this->mModel;
	}
	
	public final function getValue($pName) {
		return ($this->hasValue($pName)) ? $this->mValues[$pName] : null;
	}
	
	public final function getIdValue($pName) {
		return ($this->hasIdValue($pName)) ? $this->mIdValues[$pName] : null;
	}
	
	public final function getInstanceValue($pPropertyName, $pIsLoaded = true) {
		return $this->getProperty($pPropertyName, true)->getModel()->getObjectInstance($pIsLoaded);
	}
	
	protected final function _setValues(array $pValues) {
		$this->mValues = $pValues;
	}
	
	public final function resetValues($pResetIdValues = true) {
		$this->mValues = [];
		if ($pResetIdValues) {
			$this->mIdValues = [];
		}
	}
	
	public final function getValues() {
		return $this->mValues;
	}
	
	public final function getIdValues() {
		return $this->mIdValues;
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
	
	public final function cast(Model $pModel) {
		if ($this instanceof ObjectArray) {
			throw new \Exception('object array cannot be casted');
		}
		if (!$pModel->isInheritedFrom($this->mModel)) {
			throw new \Exception("Cannot cast object, '{$pModel->getModelName()}' is not inherited from '{$this->mModel->getModelName()}'");
		}
		$lhasCompleteId = $this->hasCompleteId();
		if ($lhasCompleteId) {
			if (MainObjectCollection::getInstance()->hasObject($this->getId(), $pModel->getModelName(), false)) {
				throw new \Exception("Cannot cast object to '{$pModel->getModelName()}'. Object with id '{$this->getId()}' and model '{$pModel->getModelName()}' already exists in MainModelCollection");
			}
		}
		$this->mModel = $pModel;
		if($this->mModel instanceof MainModel) {
			foreach ($this->mModel->getProperties() as $lProperty) {
				if ($lProperty->isComposition()) {
					$this->initValue($lProperty->getName(), false);
				}
			}
			if ($lhasCompleteId) {
				MainObjectCollection::getInstance()->addObject($this);
			}
		}
	}
	
	/**
	 * 
	 * @param string $pName
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pName) {
		return $this->getProperty($pName, true)->loadValue($this->getValue($pName), $this);
	}
	
	public final function loadValueIds($pName) {
		return $this->getProperty($pName, true)->loadValueIds($this->getValue($pName), $this);
	}
	
	public function setId($pId) {
		$lIdProperties = $this->mModel->getIdProperties();
		if (count($lIdProperties) == 1) {
			$this->setIdValue(key($lIdProperties), $pId);
		}
		else {
			$lIdValues = $this->mModel->decodeId($pId);
			if (count($lIdProperties) !== count($lIdValues)) {
				throw new \Exception('invalid id : '.$pId);
			}
			$i = 0;
			foreach ($lIdProperties as $lPropertyName => $lProperty) {
				$this->setIdValue($lPropertyName, $lIdValues[$i]);
				$i++;
			}
		}
	}
	
	public function getId() {
		$lIdProperties = $this->mModel->getIdProperties();
		if (count($lIdProperties) == 1) {
			return $this->getIdValue(key($lIdProperties));
		}
		$lValues = [];
		foreach ($lIdProperties as $lPropertyName => $lProperty) {
			$lValues[] = $this->getIdValue($lPropertyName);
		}
		return $this->mModel->encodeId($lValues);
	}
	
	public final function hasCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
			if(is_null($this->getIdValue($lPropertyName)) || $this->getIdValue($lPropertyName) == '') {
				return false;
			}
		}
		return true;
	}
	
	public final function verifCompleteId() {
		foreach ($this->mModel->getIdProperties() as $lPropertyName => $lProperty) {
			if(is_null($this->getIdValue($lPropertyName)) || $this->getIdValue($lPropertyName) == '') {
				throw new \Excpetion("id is not complete, property '$lPropertyName' is empty");
			}
		}
	}
	
	public final function setValue($pName, $pValue, $pStrict = true) {
		if ($pStrict && !is_null($pValue)) {
			if ($this instanceof ObjectArray) {
				$this->mModel->getModel()->verifValue($pValue);
			} else {
				$this->mModel->getProperty($pName, true)->getModel()->verifValue($pValue);
			}
		}
		$this->mValues[$pName] = $pValue;
	}
	
	public final function setIdValue($pName, $pValue, $pStrict = true) {
		if ($pStrict && !is_null($pValue)) {
			if ($this instanceof ObjectArray) {
				$this->mModel->getModel()->verifValue($pValue);
			} else {
				$this->mModel->getIdProperty($pName, true)->getModel()->verifValue($pValue);
			}
		}
		$this->mIdValues[$pName] = $pValue;
	}
	
	public final function setUndefinedValue($pName, $pValue, $pStrict = true) {
		if ($pStrict && !is_null($pValue)) {
			if ($this instanceof ObjectArray) {
				$this->mModel->getModel()->verifValue($pValue);
			} else {
				$this->mModel->getIdProperty($pName, true)->getModel()->verifValue($pValue);
			}
		}
		if ($this->mModel->getIdProperty($pName, true)->isId()) {
			$this->mIdValues[$pName] = $pValue;
		} else {
			$this->mValues[$pName] = $pValue;
		}
	}
	
	public final function setUndefinedValueplop($pName, $pValue, $pStrict = true) {
		if ($pStrict && !is_null($pValue)) {
			if ($this instanceof ObjectArray) {
				$this->mModel->getModel()->verifValue($pValue);
			} else {
				$this->mModel->getIdProperty($pName, true)->getModel()->verifValue($pValue);
			}
		}
		if ($this->mModel->hasIdProperty($pName)) {
			$this->mIdValues[$pName] = $pValue;
		} else {
			$this->mValues[$pName] = $pValue;
		}
	}
	
	protected final function _pushValue($pValue, $pStrict = true) {
		$this->mValues[] = $pValue;
	}
	
	public final function deleteValue($pName) {
		if ($this->hasValue($pName)) {
			unset($this->mValues[$pName]);
		}
	}
	
	public final function deleteIdValue($pName) {
		if ($this->hasIdValue($pName)) {
			unset($this->mIdValues[$pName]);
		}
	}
	
	public final function deleteId() {
		$this->mIdValues = [];
	}
	
	/**
	 * instanciate an Object and add it to values
	 * @param unknown $pPropertyName
	 * @param string $pIsLoaded
	 * @return Object
	 */
	public final function initValue($pPropertyName, $pIsLoaded = true) {
		$this->mValues[$pPropertyName] = $this->getInstanceValue($pPropertyName, $pIsLoaded);
		return $this->mValues[$pPropertyName];
	}
	
	public final function hasValue($pName) {
		return array_key_exists($pName, $this->mValues);
	}
	
	public final function hasIdValue($pName) {
		return array_key_exists($pName, $this->mIdValues);
	}
	
	public final function hasValues($Names) {
		foreach ($Names as $lName) {
			if (!$this->hasValue($lName)) {
				return false;
			}
		}
		return true;
	}
	
	public final function hasProperty($pPropertyName) {
		return $this->mModel->hasProperty($pPropertyName);
	}
	
	public final function getProperties() {
		return $this->mModel->getProperties();
	}
	
	public final function getAllPropertiesNames() {
		return array_keys($this->mModel->getProperties());
	}
	
	public final function getProperty($pPropertyName, $pThrowException = false) {
		return $this->mModel->getProperty($pPropertyName, $pThrowException);
	}
	
	/**
	 *
	 * @param string $pOperation specify it only if object serialization is sqlDatabase
	 * @throws \Exception
	 */
	public final function save($pOperation = null) {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		$this->getModel()->getSerialization()->saveObject($this, $pOperation);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                           Php Object                                          |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSerializedStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, true, true, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPublicStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, false, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPrivateStdObject($pStdObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromStdObject($pStdObject, true, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromStdObject($pStdObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromStdObject($this, $pStdObject, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function toSerialStdObject($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(true, true, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPublicStdObject($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(false, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPrivateStdObject($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toStdObject(true, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toStdObject($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->mModel->toStdObject($this, $pPrivate, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                              XML                                              |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSerializedXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, true, true, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPublicXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, false, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPrivateXml($pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromXml($pXml, true, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromXml($pXml, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromXml($this, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function toSerialXml($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toXml(true, true, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPublicXml($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toXml(false, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPrivateXml($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toXml(true, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toXml($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		$lXmlNode = new \SimpleXmlElement("<{$this->getModel()->getModelName()}/>");
		$this->mModel->toXml($this, $lXmlNode, $pPrivate, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
		return $lXmlNode;
	}

	
	/***********************************************************************************************\
	|                                                                                               |
	|                                sql database - flattened array                                 |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function fromSqlDatabase($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, true, true, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPublicFlattenedArray($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, false, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromPrivateFlattenedArray($pRow, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->fromFlattenedArray($pRow, true, false, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function fromFlattenedArray($pRow, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->mModel->fillObjectFromFlattenedArray($this, $pRow, $pPrivate, $pUseSerializationName, $pTimeZone, $pUpdateLoadStatus);
	}
	
	public final function toSqlDatabase($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(true, true, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPublicFlattenedArray($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(false, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toPrivateFlattenedArray($pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->toFlattenedArray(true, false, $pTimeZone, $pMainForeignObjects);
	}
	
	public final function toFlattenedArray($pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		return $this->mModel->toFlattenedArray($this, $pPrivate, $pUseSerializationName, $pTimeZone, $pMainForeignObjects);
	}
	
}
