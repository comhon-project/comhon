<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\serialization\SqlTable;
use comhon\object\Object;
use comhon\object\_final\Object as FinalObject;
use comhon\object\ObjectArray;
use comhon\exception\PropertyException;
use comhon\object\config\Config;
use comhon\model\property\Property;
use comhon\model\property\ForeignProperty;
use comhon\model\property\AggregationProperty;
use comhon\object\ComhonDateTime;
use comhon\utils\Utils;

abstract class Model {

	const MERGE     = 1;
	const OVERWRITE = 2;
	const NO_MERGE  = 3;
	
	const INHERITANCE_KEY = '__inheritance__';
	
	private static $sInstanceObjectHash = [];

	protected $mModelName;
	protected $mIsLoaded     = false;
	protected $mIsLoading    = false;
	
	private $mExtendsModel;
	private $mObjectClass  = 'comhon\object\_final\Object';
	private $mIsExtended   = false;
	private $mProperties   = [];
	private $mIdProperties = [];
	private $mAggregations = [];
	private $mPublicProperties  = [];
	private $mSerializableProperties = [];
	private $mPropertiesWithDefaultValues = [];
	private $mMultipleForeignProperties = [];
	private $mComplexProperties = [];
	private $mDateTimeProperties = [];
	private $mUniqueIdProperty;
	private $mHasPrivateIdProperty;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public function __construct($pModelName, $pLoadModel) {
		$this->mModelName = $pModelName;
		if ($pLoadModel) {
			$this->load();
		}
	}
	
	public final function load() {
		if (!$this->mIsLoaded && !$this->mIsLoading) {
			$this->mIsLoading = true;
			$lResult = ModelManager::getInstance()->getProperties($this);
			$this->mExtendsModel = $lResult[ModelManager::EXTENDS_MODEL];
			$this->_setProperties($lResult[ModelManager::PROPERTIES]);
			
			if (!is_null($lResult[ModelManager::OBJECT_CLASS])) {
				$this->mObjectClass = $lResult[ModelManager::OBJECT_CLASS];
				if ($this->mObjectClass !== 'comhon\object\_final\Object') {
					$this->mIsExtended = true;
				}
			}
			$this->_setSerialization();
			$this->_init();
			$this->mIsLoaded  = true;
			$this->mIsLoading = false;
		}
	}
	
	protected function _setSerialization() {}
	
	protected function _init() {
		// you can overide this function in inherited class to initialize others attributes
	}
	
	public function getObjectClass() {
		return $this->mObjectClass;
	}
	
	/**
	 * 
	 * @param boolean $pIsloaded
	 * @return Object
	 */
	public function getObjectInstance($pIsloaded = true) {
		if ($this->mIsExtended) {
			return new $this->mObjectClass($pIsloaded);
		} else {
			return new $this->mObjectClass($this, $pIsloaded);
		}
		
	}
	
	public function getExtendsModel() {
		return $this->mExtendsModel;
	}
	
	public function hasExtendsModel() {
		return !is_null($this->mExtendsModel);
	}
	
	public function isInheritedFrom(Model $pModel) {
		$lModel = $this;
		$lIsInherited = false;
		while (!is_null($lModel->mExtendsModel) && !$lIsInherited) {
			$lIsInherited = $pModel === $lModel->mExtendsModel;
			$lModel = $lModel->mExtendsModel;
		}
		return $lIsInherited;
	}
	
	/**
	 * get or create an instance of Object
	 * @param \stdClass $pStdObject
	 * @param boolean $pPrivate
	 * @param boolean $pUseSerializationName
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pStdObject->{self::INHERITANCE_KEY}) ? $pStdObject->{self::INHERITANCE_KEY} : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	/**
	 * get or create an instance of Object
	 * @param \SimpleXMLElement $pXml
	 * @param boolean $pPrivate
	 * @param boolean $pUseSerializationName
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pXml[self::INHERITANCE_KEY]) ? (string) $pXml[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	/**
	 * get or create an instance of Object
	 * @param string[] $pRow
	 * @param boolean $pPrivate
	 * @param boolean $pUseSerializationName
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = array_key_exists(self::INHERITANCE_KEY, $pRow) ? $pRow[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus, $pFlagAsUpdated);
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string $pInheritanceModelName
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status 
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel or LocalModel');
	}
	
	public function getName() {
		return $this->mModelName;
	}
	
	public function getMainModelName() {
		return $this->mModelName;
	}
	
	/**
	 * 
	 * @return Property[]
	 */
	public function getProperties() {
		return $this->mProperties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getSpecificProperties($pPrivate, $pSerialization) {
		return $pPrivate ? $this->mProperties : $this->mPublicProperties;
	}
	
	public function getComplexProperties() {
		return $this->mComplexProperties;
	}
	
	public function getDateTimeProperties() {
		return $this->mDateTimeProperties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getPublicProperties() {
		return $this->mPublicProperties;
	}
	
	public function getPropertiesNames() {
		return array_keys($this->mProperties);
	}
	
	/**
	 * 
	 * @param string $pPropertyName
	 * @param string $pThrowException
	 * @throws PropertyException
	 * @return Property
	 */
	public function getProperty($pPropertyName, $pThrowException = false) {
		if ($this->hasProperty($pPropertyName)) {
			return $this->mProperties[$pPropertyName];
		}
		else if ($pThrowException) {
			throw new PropertyException($this, $pPropertyName);
		}
		return null;
	}
	
	/**
	 *
	 * @param string $pPropertyName
	 * @param string $pThrowException
	 * @throws PropertyException
	 * @return Property
	 */
	public function getIdProperty($pPropertyName, $pThrowException = false) {
		if ($this->hasIdProperty($pPropertyName)) {
			return $this->mIdProperties[$pPropertyName];
		}
		else if ($pThrowException) {
			throw new PropertyException($this, $pPropertyName);
		}
		return null;
	}
	
	/**
	 * 
	 * @param Property[] $pProperties
	 */
	protected function _setProperties($pProperties) {
		$lPublicIdProperties = [];
		
		// first we register id properties to be sure to have them in first positions
		foreach ($pProperties as $lProperty) {
			if ($lProperty->isId()) {
				$this->mIdProperties[$lProperty->getName()] = $lProperty;
				if (!$lProperty->isPrivate()) {
					$lPublicIdProperties[$lProperty->getName()] = $lProperty;
				}
				if ($lProperty->isSerializable()) {
					$this->mSerializableProperties[$lProperty->getName()] = $lProperty;
					if (!$lProperty->isPrivate()) {
						$this->mPublicSerializableProperties[$lProperty->getName()] = $lProperty;
					}
				}
				if (!$lProperty->isPrivate()) {
					$this->mPublicProperties[$lProperty->getName()] = $lProperty;
				}
				$this->mProperties[$lProperty->getName()] = $lProperty;
			}
		}
		// second we register others properties
		foreach ($pProperties as $lProperty) {
			if (!$lProperty->isId()) {
				if ($lProperty->hasDefaultValue()) {
					$this->mPropertiesWithDefaultValues[$lProperty->getName()] = $lProperty;
				} else if ($lProperty->isAggregation()) {
					$this->mAggregations[$lProperty->getName()] = $lProperty;
				} else if ($lProperty->hasMultipleSerializationNames()) {
					$this->mMultipleForeignProperties[$lProperty->getName()] = $lProperty;
				}
				if ($lProperty->isSerializable()) {
					$this->mSerializableProperties[$lProperty->getName()] = $lProperty;
					if (!$lProperty->isPrivate()) {
						$this->mPublicSerializableProperties[$lProperty->getName()] = $lProperty;
					}
				}
				if (!$lProperty->isPrivate()) {
					$this->mPublicProperties[$lProperty->getName()] = $lProperty;
				}
				if ($lProperty->isComplex()) {
					$this->mComplexProperties[$lProperty->getName()] = $lProperty;
				}
				if ($lProperty->hasModelDateTime()) {
					$this->mDateTimeProperties[$lProperty->getName()] = $lProperty;
				}
				$this->mProperties[$lProperty->getName()] = $lProperty;
			}
		}
		if (count($this->mIdProperties) == 1) {
			reset($this->mIdProperties);
			$this->mUniqueIdProperty = current($this->mIdProperties);
		}
		if (count($this->mIdProperties) != count($lPublicIdProperties)) {
			$this->mHasPrivateIdProperty = true;
		}
	}
	
	public function hasProperty($pPropertyName) {
		return array_key_exists($pPropertyName, $this->mProperties);
	}
	
	public function hasIdProperty($pPropertyName) {
		return array_key_exists($pPropertyName, $this->mIdProperties);
	}
	
	/**
	 * get foreign properties that have their own serialization
	 * @param string $pSerializationType ("sqlTable", "jsonFile"...)
	 * @return Property[]
	 */
	public function getForeignSerializableProperties($pSerializationType) {
		$lProperties = [];
		foreach ($this->mProperties as $lPropertyName => $lProperty) {
			if (($lProperty instanceof ForeignProperty) && $lProperty->hasSerializationUnit($pSerializationType)) {
				$lProperties[] = $lProperty;
			}
		}
		return $lProperties;
	}
	
	public function getSerializableProperties() {
		return $this->mSerializableProperties;
	}
	
	public function getIdProperties() {
		return $this->mIdProperties;
	}
	
	/**
	 * get id property if there is one and only one id property
	 * @return Property|null
	 */
	public function getUniqueIdProperty() {
		return $this->mUniqueIdProperty;
	}
	
	public function hasUniqueIdProperty() {
		return !is_null($this->mUniqueIdProperty);
	}
	
	public function hasPrivateIdProperty() {
		return $this->mHasPrivateIdProperty;
	}
	
	public function hasIdProperties() {
		return !empty($this->mIdProperties);
	}
	
	/**
	 * 
	 * @return Property:
	 */
	public function getPropertiesWithDefaultValues() {
		return $this->mPropertiesWithDefaultValues;
	}
	
	/**
	 * 
	 * @return AggregationProperty[]:
	 */
	public function getAggregations() {
		return $this->mAggregations;
	}
	
	public function getSerializationIds() {
		$lSerializationIds = [];
		foreach ($this->mIdProperties as $lIdProperty) {
			$lSerializationIds[] = $lIdProperty->getSerializationName();
		}
		return $lSerializationIds;
	}
	
	public function getFirstIdProperty() {
		reset($this->mIdProperties);
		return empty($this->mIdProperties) ? null : current($this->mIdProperties);
	}
	
	public function isLoaded() {
		return $this->mIsLoaded;
	}
	
	public function isComplex() {
		return true;
	}
	
	/**
	 * @return null
	 */
	public function getSerialization() {
		return null;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return false;
	}
	
	public function hasPartialSerialization() {
		return false;
	}
	
	/**
	 * @return null
	 */
	public function getSerializationSettings() {
		return null;
	}
	
	public function hasSqlTableUnit() {
		return false;
	}
	
	public function getSqlTableUnit() {
		return nul;
	}
	
	/**
	 * @param array $pIdValues encode id in json format
	 */
	public function encodeId($pIdValues) {
		if (empty($pIdValues)) {
			return null;
		}
		$i = 0;
		foreach ($this->getIdProperties() as $lPropertyName => $lProperty) {
			if (!is_null($pIdValues[$i]) && !$lProperty->getModel()->isCheckedValueType($pIdValues[$i])) {
				$pIdValues[$i] = $lProperty->getModel()->castValue($pIdValues[$i]);
			}
			$i++;
		}
		return json_encode($pIdValues);
	}
	
	/**
	 * @param string $pId decode id from json format
	 */
	public function decodeId($pId) {
		return json_decode($pId);
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param ObjectCollection $pLocalObjectCollection
	 * @return ObjectCollection
	 */
	protected function _getLocalObjectCollection($pObject, $pLocalObjectCollection) {
		return $pLocalObjectCollection;
	}
	
	protected function _addMainCurrentObject(Object $pObject, &$pMainForeignObjects = null) {
		if (($pObject->getModel() instanceof MainModel) && is_array($pMainForeignObjects) && !is_null($pObject->getId()) && $pObject->hasCompleteId()) {
			$pMainForeignObjects[$pObject->getModel()->getName()][$pObject->getId()] = null;
		}
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param array $pMainForeignObjects
	 */
	protected function _removeMainCurrentObject(Object $pObject, &$pMainForeignObjects = null) {
		if (is_array($pMainForeignObjects) && !is_null($pObject->getId()) && $pObject->hasCompleteId()) {
			unset($pMainForeignObjects[$pObject->getModel()->getName()][$pObject->getId()]);
		}
	}
	
	/**
	 * get object with filtered values, return new instance if need to be filtered otherwise return specified object
	 * @param Object $pObject
	 * @param string[]|null $pPropertiesFilter
	 * @return Object
	 */
	protected function _getFilteredObject(Object $pObject, $pPropertiesFilter, $pCheckProperties = true) {
		if (empty($pPropertiesFilter)) {
			return $pObject;
		}
		if ($pCheckProperties) {
			$pPropertiesFilter = array_unique($pPropertiesFilter);
		}
		$lValuesCount = $pObject->getValuesCount();
		foreach ($pPropertiesFilter as $lPropertyFilter) {
			if ($pObject->hasValue($lPropertyFilter)) {
				$lValuesCount--;
			}
		}
		if ($lValuesCount == 0) {
			return $pObject;
		}
		$lNewObject = new FinalObject($pObject->getModel());
		$lNewObject->reset();
		foreach ($pPropertiesFilter as $lPropertyFilter) {
			if ($pObject->hasValue($lPropertyFilter)) {
				$lNewObject->setValue($lPropertyFilter, $pObject->getValue($lPropertyFilter), $pObject->isValueFlagedAsUpdated($lPropertyFilter), false);
			}
		}
		return $lNewObject;
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param boolean $pPrivate
	 * @param boolean $pUseSerializationName
	 * @param string $pTimeZone 
	 * @param boolean $pUpdatedValueOnly 
	 * @param string[] $pPropertiesFilter
	 * @param array|null $pMainForeignObjects export foreign objects with main model if array specified
	 * @return NULL|\stdClass
	 */
	public function toStdObject(Object $pObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lObject = $this->_getFilteredObject($pObject, $pPropertiesFilter);
		$lStdObject = $this->_toStdObject($lObject, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pUpdatedValueOnly, $pUpdatedValueOnly, $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lStdObject;
	}
		
	protected function _toStdObject(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lReturn = new \stdClass();
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getName()}' can't be exported");
				return $this->_toStdObjectId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName) && (!$pUpdatedValueOnly || $lProperty->isId() || $pObject->isUpdatedValue($lPropertyName))) {
					if ($pUseSerializationName) {
						$lReturn->{$lProperty->getSerializationName()} = $lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					} else {
						$lReturn->$lPropertyName = $lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						if (!$pUpdatedValueOnly || $pObject->getValue($lPropertyName)->isUpdatedValue($lIdProperty->getName())) {
							$lReturn->$lSerializationName = $lIdProperty->getModel()->_toStdObject(
								$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects
							);
						}
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lReturn->{self::INHERITANCE_KEY} = $pObject->getModel()->getName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	protected function _toStdObjectId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lObjectId = new \stdClass();
			$lObjectId->id = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
			$lObjectId->{self::INHERITANCE_KEY} = $pObject->getModel()->getName();
			return $lObjectId;
		}
		return $this->_toId($pObject, $pUseSerializationName);
	}
	
	public function toXml(Object $pObject, $pXmlNode, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lObject = $this->_getFilteredObject($pObject, $pPropertiesFilter);
		$lXml = $this->_toXml($lObject, $pXmlNode, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pUpdatedValueOnly, $pUpdatedValueOnly, $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lXml;
	}
		
	protected function _toXml(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getName()}' can't be exported");
				$this->_toXmlId($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly);
				return;
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName) && !is_null($lValue) && (!$pUpdatedValueOnly || $lProperty->isId() || $pObject->isUpdatedValue($lPropertyName))) {
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					if ($lProperty->isInterfacedAsNodeXml()) {
						if (($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum)) {
							$lValue = $lProperty->getModel()->_toXml($lValue, null, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
							$pXmlChildNode = $pXmlNode->addChild($lName, $lValue);
						} else {
							$pXmlChildNode = $pXmlNode->addChild($lName);
							$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
						}
					} else {
						$pXmlNode[$lName] = $lProperty->getModel()->_toXml($lValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$pXmlChildNode = new \SimpleXMLElement('<root/>');
					$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						if (!$pUpdatedValueOnly || $pObject->getValue($lPropertyName)->isUpdatedValue($lIdProperty->getName())) {
							$pXmlNode[$lSerializationName] = $lIdProperty->getModel()->_toXml(
								$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects
							);
						}
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$pXmlNode[self::INHERITANCE_KEY] = $pObject->getModel()->getName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return null;
	}
	
	protected function _toXmlId(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lDomNode  = dom_import_simplexml($pXmlNode);
		$lId       = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
		$lTextNode = new \DOMText($lId);
		$lDomNode->appendChild($lTextNode);
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$pXmlNode[self::INHERITANCE_KEY] = $pObject->getModel()->getName();
		}
		return $lId;
	}
	
	public function toFlattenedArray(Object $pObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdatedValueOnly = false, $pPropertiesFilter = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lObject = $this->_getFilteredObject($pObject, $pPropertiesFilter);
		$lArray = $this->_toFlattenedArray($lObject, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pUpdatedValueOnly, $pUpdatedValueOnly, $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lArray;
	}
	
	protected function _toFlattenedArray(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lFlattenedArray = [];
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getName()}' can't be exported");
				return $this->_toFlattenedValueId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName) && (!$pUpdatedValueOnly || $lProperty->isId() || $pObject->isUpdatedValue($lPropertyName))) {
					$lFlattenedValue = $lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					$lPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					if (is_array($lFlattenedValue)) {
						if ($lProperty->isForeign() && !$lProperty->isAggregation() && $lProperty->hasSqlTableUnit()) {
							$lFlattenedArray[$lPropertyName] = $lFlattenedValue['id'];
						} else {
							$lFlattenedArray[$lPropertyName] = json_encode($lFlattenedValue);
						}
					} else {
						$lFlattenedArray[$lPropertyName] = $lFlattenedValue;
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						if (!$pUpdatedValueOnly || $pObject->getValue($lPropertyName)->isUpdatedValue($lIdProperty->getName())) {
							$lFlattenedArray[$lSerializationName] = $lIdProperty->getModel()->_toFlattenedValue(
								$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects
							);
						}
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lFlattenedArray[self::INHERITANCE_KEY] = $pObject->getModel()->getName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lFlattenedArray;
	}
	
	protected function _toFlattenedValue(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lReturn = [];
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getName()}' can't be exported");
				return $this->_toFlattenedValueId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName) && (!$pUpdatedValueOnly || $lProperty->isId() || $pObject->isUpdatedValue($lPropertyName))) {
					if ($pUseSerializationName) {
						$lReturn[$lProperty->getSerializationName()] = $lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					} else {
						$lReturn[$lPropertyName] = $lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						if (!$pUpdatedValueOnly || $pObject->getValue($lPropertyName)->isUpdatedValue($lIdProperty->getName())) {
							$lReturn[$lSerializationName] = $lIdProperty->getModel()->_toFlattenedValue(
								$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, false, $pOriginalUpdatedValueOnly, $pMainForeignObjects
							);
						}
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lReturn[self::INHERITANCE_KEY] = $pObject->getModel()->getName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	protected function _toFlattenedValueId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lArrayId = [];
			$lArrayId['id'] = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
			$lArrayId[self::INHERITANCE_KEY] = $pObject->getModel()->getName();
			return $lArrayId;
		}
		return $this->_toId($pObject, $pUseSerializationName);
	}
	
	public function _toId(Object $pObject, $pUseSerializationName = false) {
		if (!$pObject->hasCompleteId()) {
			throw new \Exception("Warning cannot export id of foreign property with model '{$this->mModelName}' because object doesn't have complete id");
		}
		return $pObject->getId();
	}
	
	public function fillObjectFromStdObject(Object $pObject, $pStdObject, $pPrivate = false, $pUseSerializationName = false,  $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromFlattenedArray(Object $pObject, $pRow, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	protected function _fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		if (is_null($pStdObject)) {
			return null;
		}
		$lObject = $this->_getOrCreateObjectInstanceFromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection);
		$this->_fillObjectFromStdObject($lObject, $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	protected function _fillObjectFromStdObject(Object $pObject, \stdClass $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		$lProperties = $lModel->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($lProperties as $lPropertyName => $lProperty) {
			if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lStdObjectPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lPropertyName;
				if (isset($pStdObject->$lStdObjectPropertyName)) {
					if (is_null($pStdObject->$lStdObjectPropertyName)) {
						$pObject->setValue($lPropertyName, null, $pFlagAsUpdated);
					} else {
						$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromStdObject($pStdObject->$lStdObjectPropertyName, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
					}
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					if (isset($pStdObject->$lSerializationName)) {
						$lId[] = $pStdObject->$lSerializationName;
					}
				}
				if (count($lId) == count($lMultipleForeignProperty->getMultipleIdProperties())) {
					$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
				}
			}
		}
	}
	
	protected function _fromXml($pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection)) ? $lObject : null;
	}
	
	protected function _fillObjectFromXml(Object $pObject, \SimpleXMLElement $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lHasValue = false;
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		$lProperties = $lModel->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($lProperties as $lPropertyName => $lProperty) {
			if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lXmlPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lPropertyName;
				if (!$lProperty->isInterfacedAsNodeXml()) {
					if (isset($pXml[$lXmlPropertyName])) {
						$pObject->setValue($lPropertyName,  $lProperty->getModel()->_fromXml($pXml[$lXmlPropertyName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
						$lHasValue = true;
					}
				} else if (isset($pXml->$lXmlPropertyName)) {
					$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromXml($pXml->$lXmlPropertyName, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
					$lHasValue = true;
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					if (isset($pXml[$lSerializationName])) {
						$lId[] = $lIdProperty->getModel()->_fromXml($pXml[$lSerializationName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection);
					}
				}
				if (count($lId) == count($lMultipleForeignProperty->getMultipleIdProperties())) {
					$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
				}
			}
		}
		return $lHasValue;
	}
	
	protected function _fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, $pLocalObjectCollection);
		$this->_fillObjectFromFlattenedArray($lObject, $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	
	public function _fillObjectFromFlattenedArray(Object $pObject, array $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		$lProperties = $lModel->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($lProperties as $lPropertyName => $lProperty) {
			if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lFlattenedPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lPropertyName;
				if (array_key_exists($lFlattenedPropertyName, $pRow)) {
					if (is_null($pRow[$lFlattenedPropertyName])) {
						continue;
					}
					$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromFlattenedValue($pRow[$lFlattenedPropertyName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					if (isset($pRow[$lSerializationName])) {
						$lId[] = $pRow[$lSerializationName];
					}
				}
				if (count($lId) == count($lMultipleForeignProperty->getMultipleIdProperties())) {
					$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection), $pFlagAsUpdated);
				}
			}
		}
	}
	
	protected function _fromFlattenedValue($pJsonEncodedObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lStdObject = json_decode($pJsonEncodedObject);
		return $this->_fromStdObject($lStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection);
	}
	
	protected function _fromStdObjectId($pValue, $pFlagAsUpdated, $pLocalObjectCollection) {
		if (is_object($pValue)) {
			if (!isset($pValue->id) || !isset($pValue->{self::INHERITANCE_KEY})) {
				throw new \Exception('object id must have property \'id\' and \''.self::INHERITANCE_KEY.'\', current object id is : '.json_encode($pValue));
			}
			$lId = $pValue->id;
			$lInheritance = $pValue->{self::INHERITANCE_KEY};
		}
		else {
			$lId = $pValue;
			$lInheritance = null;
		}
		return $this->_fromId($lId, $lInheritance, $pFlagAsUpdated, $pLocalObjectCollection);
	}
	
	protected function _fromXmlId($pValue, $pFlagAsUpdated, $pLocalObjectCollection) {
		$lId = (string) $pValue;
		if ($lId == '') {
			return null;
		}
		$lInheritance = isset($pValue[self::INHERITANCE_KEY]) ? (string) $pValue[self::INHERITANCE_KEY] : null;
		return $this->_fromId($lId, $lInheritance, $pFlagAsUpdated, $pLocalObjectCollection);
	}
	
	protected function _fromFlattenedValueId($pValue, $pFlagAsUpdated, $pLocalObjectCollection) {
		if (is_null($pValue)) {
			return null;
		}
		$lValue = json_decode($pValue);
		if (is_object($lValue)) {
			if (!isset($lValue->id) || !isset($lValue->{self::INHERITANCE_KEY})) {
				throw new \Exception('object id must have property \'id\' and \''.self::INHERITANCE_KEY.'\', current object id is : '.json_encode($lValue));
			}
			$lId = $lValue->id;
			$lInheritance = $lValue->{self::INHERITANCE_KEY};
		} else {
			$lId = $pValue;
			$lInheritance = null;
		}
		return $this->_fromId($lId, $lInheritance, $pFlagAsUpdated, $pLocalObjectCollection);
	}
	
	protected function _fromId($pId, $pInheritanceModelName, $pFlagAsUpdated, $pLocalObjectCollection = null) {
		if (is_object($pId) || $pId === '') {
			$pId = is_object($pId) ? json_encode($pId) : $pId;
			throw new \Exception("malformed id '$pId' for model '{$this->mModelName}'");
		}
		if (is_null($pId)) {
			return null;
		}

		return $this->_getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, false, false, $pFlagAsUpdated);
	}
	
	protected function _buildObjectFromId($pId, $pIsloaded, $pFlagAsUpdated) {
		return $this->_fillObjectwithId($this->getObjectInstance($pIsloaded), $pId, $pFlagAsUpdated);
	}
	
	protected function _fillObjectwithId(Object $pObject, $pId, $pFlagAsUpdated) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('object doesn\'t have good model');
		}
		if (!is_null($pId)) {
			$pObject->setId($pId, $pFlagAsUpdated);
		}
		return $pObject;
	}
	
	public function getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName) {
		if (!is_null($this->mUniqueIdProperty)) {
			if (!$this->mUniqueIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				return null;
			}
			$lPropertyName = $pUseSerializationName ? $this->mUniqueIdProperty->getSerializationName() : $this->mUniqueIdProperty->getName();
			return isset($pStdObject->$lPropertyName) ? $this->mUniqueIdProperty->getModel()->_fromStdObject($pStdObject->$lPropertyName) : null;
		}
		$lIdValues = [];
		foreach ($this->getIdProperties() as $lIdProperty) {
			if ($lIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lPropertyName = $pUseSerializationName ? $lIdProperty->getSerializationName() : $lIdProperty->getName();
				if (isset($pStdObject->$lPropertyName)) {
					$lIdValues[] = $lIdProperty->getModel()->_fromStdObject($pStdObject->$lPropertyName);
				} else {
					$lIdValues[] = null;
				}
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromXml($pXml, $pPrivate, $pUseSerializationName) {
		if (!is_null($this->mUniqueIdProperty)) {
			if (!$this->mUniqueIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				return null;
			}
			$lPropertyName = $pUseSerializationName ? $this->mUniqueIdProperty->getSerializationName() : $this->mUniqueIdProperty->getName();
			if ($this->mUniqueIdProperty->isInterfacedAsNodeXml()) {
				return isset($pXml->$lPropertyName) ? $this->mUniqueIdProperty->getModel()->_fromXml($pXml->$lPropertyName) : null;
			} else {
				return isset($pXml[$lPropertyName]) ? $this->mUniqueIdProperty->getModel()->_fromXml($pXml[$lPropertyName]) : null;
			}
		}
		$lIdValues = [];
		foreach ($this->getIdProperties() as $lIdProperty) {
			if ($lIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lPropertyName = $pUseSerializationName ? $lIdProperty->getSerializationName() : $lIdProperty->getName();
				if ($lIdProperty->isInterfacedAsNodeXml()) {
					$lIdValues[] = isset($pXml->$lPropertyName) ? $lIdProperty->getModel()->_fromXml($pXml->$lPropertyName) : null;
				} else {
					$lIdValues[] = isset($pXml[$lPropertyName]) ? $lIdProperty->getModel()->_fromXml($pXml[$lPropertyName]) : null;
				}
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName) {
		if (!is_null($this->mUniqueIdProperty)) {
			if (!$this->mUniqueIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				return null;
			}
			$lPropertyName = $pUseSerializationName ? $this->mUniqueIdProperty->getSerializationName() : $this->mUniqueIdProperty->getName();
			return isset($pRow[$lPropertyName]) ? $this->mUniqueIdProperty->getModel()->_fromFlattenedValue($pRow[$lPropertyName]) : null;
		}
		$lIdValues = [];
		foreach ($this->getIdProperties() as $lIdProperty) {
			if ($lIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lPropertyName = $pUseSerializationName ? $lIdProperty->getSerializationName() : $lIdProperty->getName();
				if (isset($pRow[$lPropertyName])) {
					$lIdValues[] = $lIdProperty->getModel()->_fromFlattenedValue($pRow[$lPropertyName]);
				} else {
					$lIdValues[] = null;
				}
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	/**
	 * @param Object $pValue
	 */
	public function verifValue($pValue) {
		if (!is_a($pValue, $this->mObjectClass) || ($pValue->getModel() !== $this && !$pValue->getModel()->isInheritedFrom($this))) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an instance of $this->mObjectClass, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}