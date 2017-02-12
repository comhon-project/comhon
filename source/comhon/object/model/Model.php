<?php
namespace comhon\object\model;

use comhon\object\singleton\ModelManager;
use comhon\object\object\serialization\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\exception\PropertyException;
use comhon\object\object\config\Config;

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
	private $mObjectClass  = 'comhon\object\object\Object';
	private $mProperties   = [];
	private $mIdProperties = [];
	private $mAggregations = [];
	private $mPublicProperties  = [];
	private $mSerializableProperties = [];
	private $mPublicSerializableProperties = [];
	private $mPropertiesWithDefaultValues = [];
	private $mMultipleForeignProperties = [];
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
		return new $this->mObjectClass($this, $pIsloaded);
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
	protected function _getOrCreateObjectInstanceFromObject($pStdObject, $pPrivate, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pStdObject->{self::INHERITANCE_KEY}) ? $pStdObject->{self::INHERITANCE_KEY} : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
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
	protected function _getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pXml[self::INHERITANCE_KEY]) ? (string) $pXml[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
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
	protected function _getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = array_key_exists(self::INHERITANCE_KEY, $pRow) ? $pRow[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
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
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel or LocalModel');
	}
	
	public function getModelName() {
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
		if ($pPrivate) {
			if ($pSerialization) {
				return $this->mSerializableProperties;
			} else {
				return $this->mProperties;
			}
		} else {
			if ($pSerialization) {
				return $this->mPublicSerializableProperties;
			} else {
				return $this->mPublicProperties;
			}
		}
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
	 * @return AggregationProperty:
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
	
	/**
	 * @return null
	 */
	public function getSerialization() {
		return null;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return false;
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
			$pMainForeignObjects[$pObject->getModel()->getModelName()][$pObject->getId()] = null;
		}
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param array $pMainForeignObjects
	 */
	protected function _removeMainCurrentObject(Object $pObject, &$pMainForeignObjects = null) {
		if (is_array($pMainForeignObjects) && !is_null($pObject->getId()) && $pObject->hasCompleteId()) {
			unset($pMainForeignObjects[$pObject->getModel()->getModelName()][$pObject->getId()]);
		}
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param boolean $pPrivate
	 * @param boolean $pUseSerializationName
	 * @param array|null $pMainForeignObjects 
	 * by default foreign properties with MainModel are not exported 
	 * but you can export them by spsifying an array in third parameter
	 * @return NULL|\stdClass
	 */
	public function toStdObject(Object $pObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lStdObject = $this->_toStdObject($pObject, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lStdObject;
	}
		
	protected function _toStdObject(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = new \stdClass();
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				return $this->_toStdObjectId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
					if ($pUseSerializationName) {
						$lReturn->{$lProperty->getSerializationName()} = $lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					} else {
						$lReturn->$lPropertyName = $lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						$lReturn->$lSerializationName = $lIdProperty->getModel()->_toStdObject(
							$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects
						);
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lReturn->{self::INHERITANCE_KEY} = $pObject->getModel()->getModelName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	protected function _toStdObjectId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lObjectId = new \stdClass();
			$lObjectId->id = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
			$lObjectId->{self::INHERITANCE_KEY} = $pObject->getModel()->getModelName();
			return $lObjectId;
		}
		return $this->_toId($pObject, $pUseSerializationName);
	}
	
	public function toXml(Object $pObject, $pXmlNode, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lResult = $this->_toXml($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lResult;
	}
		
	protected function _toXml(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				$this->_toXmlId($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone);
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
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName) && !is_null($lValue)) {
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					if ($lProperty->isInterfacedAsNodeXml()) {
						if (($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum)) {
							$lValue = $lProperty->getModel()->_toXml($lValue, null, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
							$pXmlChildNode = $pXmlNode->addChild($lName, $lValue);
						} else {
							$pXmlChildNode = $pXmlNode->addChild($lName);
							$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
						}
					} else {
						$pXmlNode[$lName] = $lProperty->getModel()->_toXml($lValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$pXmlChildNode = new SimpleXMLElement('<root/>');
					$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						$pXmlNode[$lSerializationName] = $lIdProperty->getModel()->_toXml(
							$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects
						);
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$pXmlNode[self::INHERITANCE_KEY] = $pObject->getModel()->getModelName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return null;
	}
	
	protected function _toXmlId(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lDomNode  = dom_import_simplexml($pXmlNode);
		$lId       = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
		$lTextNode = new \DOMText($lId);
		$lDomNode->appendChild($lTextNode);
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$pXmlNode[self::INHERITANCE_KEY] = $pObject->getModel()->getModelName();
		}
		return $lId;
	}
	
	public function toFlattenedArray(Object $pObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		self::$sInstanceObjectHash = [];
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lArray = $this->_toFlattenedArray($pObject, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lArray;
	}
	
	protected function _toFlattenedArray(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lFlattenedArray = $this->_toFlattenedValue($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		foreach ($pObject->getModel()->getProperties() as $lProperty) {
			$lPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
			if (isset($lFlattenedArray[$lPropertyName]) && is_array($lFlattenedArray[$lPropertyName])) {
				if ($lProperty->isForeign() && $lProperty->hasSqlTableUnit()) {
					$lFlattenedArray[$lPropertyName] = $lFlattenedArray[$lPropertyName]['id'];
				} else {
					$lFlattenedArray[$lPropertyName] = json_encode($lFlattenedArray[$lPropertyName]);
				}
			}
		}
		return $lFlattenedArray;
	}
	
	protected function _toFlattenedValue(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = [];
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				return $this->_toFlattenedValueId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		$lProperties = $pObject->getModel()->getSpecificProperties($pPrivate, $pUseSerializationName);
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if (array_key_exists($lPropertyName, $lProperties)) {
				$lProperty = $lProperties[$lPropertyName];
				if ($lProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
					if ($pUseSerializationName) {
						$lReturn[$lProperty->getSerializationName()] = $lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					} else {
						$lReturn[$lPropertyName] = $lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					}
				} else if ($lProperty->isForeign() && is_array($pMainForeignObjects)) {
					$lProperty->getModel()->_toFlattenedValue($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				if ($pObject->hasValue($lPropertyName) && ($pObject->getValue($lPropertyName) instanceof Object)) {
					foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						$lReturn[$lSerializationName] = $lIdProperty->getModel()->_toFlattenedValue(
							$pObject->getValue($lPropertyName)->getValue($lIdProperty->getName()), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects
						);
					}
				}
			}
		}
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lReturn[self::INHERITANCE_KEY] = $pObject->getModel()->getModelName();
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	protected function _toFlattenedValueId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			if (!$pObject->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$lArrayId = [];
			$lArrayId['id'] = $pObject->getModel()->_toId($pObject, $pUseSerializationName);
			$lArrayId[self::INHERITANCE_KEY] = $pObject->getModel()->getModelName();
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
	
	public function fillObjectFromStdObject(Object $pObject, $pStdObject, $pPrivate = false, $pUseSerializationName = false,  $pTimeZone = null, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromFlattenedArray(Object $pObject, $pRow, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	protected function _fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pStdObject)) {
			return null;
		}
		$lObject = $this->_getOrCreateObjectInstanceFromObject($pStdObject, $pPrivate, $pUseSerializationName, $pLocalObjectCollection);
		$this->_fillObjectFromStdObject($lObject, $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	protected function _fillObjectFromStdObject(Object $pObject, \stdClass $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
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
						$pObject->setValue($lPropertyName, null);
					} else {
						$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromStdObject($pStdObject->$lStdObjectPropertyName, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
					}
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					$lId[] = isset($pStdObject->$lSerializationName) ? $pStdObject->$lSerializationName : null;
				}
				$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
			}
		}
	}
	
	protected function _fromXml($pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection)) ? $lObject : null;
	}
	
	protected function _fillObjectFromXml(Object $pObject, \SimpleXMLElement $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
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
						$pObject->setValue($lPropertyName,  $lProperty->getModel()->_fromXml($pXml[$lXmlPropertyName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
						$lHasValue = true;
					}
				} else if (isset($pXml->$lXmlPropertyName)) {
					$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromXml($pXml->$lXmlPropertyName, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
					$lHasValue = true;
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					$lId[] = isset($pXml[$lSerializationName]) ? $lIdProperty->getModel()->_fromXml($pXml[$lSerializationName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) : null;
				}
				$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
			}
		}
		return $lHasValue;
	}
	
	protected function _fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pLocalObjectCollection);
		$this->_fillObjectFromFlattenedArray($lObject, $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	
	public function _fillObjectFromFlattenedArray(Object $pObject, array $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
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
					$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromFlattenedValue($pRow[$lFlattenedPropertyName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
				}
			}
		}
		if ($pUseSerializationName) {
			foreach ($this->mMultipleForeignProperties as $lPropertyName => $lMultipleForeignProperty) {
				$lId = [];
				foreach ($lMultipleForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					$lId[] = isset($pRow[$lSerializationName]) ? $pRow[$lSerializationName] : null;
				}
				$pObject->setValue($lPropertyName, $lMultipleForeignProperty->getModel()->_fromStdObject(json_encode($lId), $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
			}
		}
	}
	
	protected function _fromFlattenedValue($pJsonEncodedObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lStdObject = json_decode($pJsonEncodedObject);
		return $this->_fromStdObject($lStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection);
	}
	
	protected function _fromStdObjectId($pValue, $pLocalObjectCollection) {
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
		return $this->_fromId($lId, $lInheritance, $pLocalObjectCollection);
	}
	
	protected function _fromXmlId($pValue, $pLocalObjectCollection) {
		$lId = (string) $pValue;
		if ($lId == '') {
			return null;
		}
		$lInheritance = isset($pValue[self::INHERITANCE_KEY]) ? (string) $pValue[self::INHERITANCE_KEY] : null;
		return $this->_fromId($lId, $lInheritance, $pLocalObjectCollection);
	}
	
	protected function _fromFlattenedValueId($pValue, $pLocalObjectCollection) {
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
		return $this->_fromId($lId, $lInheritance, $pLocalObjectCollection);
	}
	
	protected function _fromId($pId, $pInheritanceModelName, $pLocalObjectCollection = null) {
		if (is_object($pId) || $pId === '') {
			$pId = is_object($pId) ? json_encode($pId) : $pId;
			throw new \Exception("malformed id '$pId' for model '{$this->mModelName}'");
		}
		if (is_null($pId)) {
			return null;
		}

		return $this->_getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, false, false);
	}
	
	protected function _buildObjectFromId($pId, $pIsloaded) {
		return $this->_fillObjectwithId($this->getObjectInstance($pIsloaded), $pId);
	}
	
	protected function _fillObjectwithId(Object $pObject, $pId) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('object doesn\'t have good model');
		}
		if (!is_null($pId)) {
			$pObject->setId($pId);
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
			return isset($pXml[$lPropertyName]) ? $this->mUniqueIdProperty->getModel()->_fromXml($pXml[$lPropertyName]) : null;
		}
		$lIdValues = [];
		foreach ($this->getIdProperties() as $lIdProperty) {
			if ($lIdProperty->isInterfaceable($pPrivate, $pUseSerializationName)) {
				$lPropertyName = $pUseSerializationName ? $lIdProperty->getSerializationName() : $lIdProperty->getName();
				if (isset($pXml[$lPropertyName])) {
					$lIdValues[] = $lIdProperty->getModel()->_fromXml($pXml[$lPropertyName]);
				} else {
					$lIdValues[] = null;
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
		if (!is_a($pValue, $this->mObjectClass)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an instance of $this->mObjectClass, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}