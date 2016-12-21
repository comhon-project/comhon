<?php
namespace comhon\object\model;

use comhon\object\singleton\InstanceModel;
use comhon\object\object\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\exception\PropertyException;
use comhon\object\object\Config;

abstract class Model {

	const MERGE     = 1;
	const OVERWRITE = 2;
	const NO_MERGE  = 3;
	
	const INHERITANCE_KEY = '__inheritance__';
	
	private static $sInstanceObjectHash = [];

	protected $mModelName;
	protected $mIsLoaded     = false;
	protected $mIsLoading    = false;
	
	private $mProperties;
	private $mExtendsModel;
	private $mObjectClass     = 'comhon\object\object\Object';
	private $mIds             = [];
	private $mEscapedDbColumn = [];
	private $mCompositions    = [];
	private $mPropertiesWithDefaultValues = [];
	
	private static $sDbColumnToEscape = [
	'integer' => null
	];
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
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
			$lResult = InstanceModel::getInstance()->getProperties($this);
			$this->mProperties = $lResult[InstanceModel::PROPERTIES];
			$this->mExtendsModel = $lResult[InstanceModel::EXTENDS_MODEL];
			foreach ($this->mProperties as $lProperty) {
				if ($lProperty->isId()) {
					$this->mIds[] = $lProperty->getName();
				}
			}
			if (!is_null($lResult[InstanceModel::OBJECT_CLASS])) {
				$this->mObjectClass = $lResult[InstanceModel::OBJECT_CLASS];
			}
			$this->_setSerialization();
			$this->_init();
			$this->mIsLoaded  = true;
			$this->mIsLoading = false;
			
			foreach ($this->mProperties as $lPropertyName => $lProperty) {
				if (array_key_exists($lProperty->getSerializationName(), self::$sDbColumnToEscape)) {
					$this->mEscapedDbColumn[$lProperty->getSerializationName()] = '`'.$lProperty->getSerializationName().'`';
				}
				if ($lProperty->hasDefaultValue()) {
					$this->mPropertiesWithDefaultValues[] = $lProperty;
				} else if ($lProperty->isComposition()) {
					$this->mCompositions[] = $lProperty;
				}
			}
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
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromObject($pStdObject, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pStdObject->{self::INHERITANCE_KEY}) ? $pStdObject->{self::INHERITANCE_KEY} : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromStdObject($pStdObject, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
	}
	
	/**
	 * get or create an instance of Object
	 * @param \SimpleXMLElement $pXml
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromXml($pXml, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = isset($pXml[self::INHERITANCE_KEY]) ? (string) $pXml[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
	}
	
	/**
	 * get or create an instance of Object
	 * @param string[] $pRow
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceFromFlattenedArray($pRow, $pUseSerializationName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		$lInheritanceModelName = array_key_exists(self::INHERITANCE_KEY, $pRow) ? $pRow[self::INHERITANCE_KEY] : null;
		return $this->_getOrCreateObjectInstance($this->getIdFromFlattenedArray($pRow, $pUseSerializationName), $lInheritanceModelName, $pLocalObjectCollection, $pIsloaded, $pUpdateLoadStatus);
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string $pInheritanceModelName
	 * @param LocalObjectCollection $pLocalObjectCollection
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
	
	protected function _setProperties($pProperties) {
		$this->mProperties = array();
		$this->mIds        = array();
		foreach ($pProperties as $pProperty) {
			$this->mProperties[$pProperty->getName()] = $pProperty;
			if ($pProperty->isId()) {
				$this->mIds[] = $pProperty->getName();
			}
		}
	}
	
	public function getPropertyModel($pPropertyName) {
		return $this->hasProperty($pPropertyName) ? $this->mProperties[$pPropertyName]->getModel() : null;
	}
	
	public function hasProperty($pPropertyName) {
		return array_key_exists($pPropertyName, $this->mProperties);
	}
	
	/**
	 * @param unknown $pSerializationType ("sqlTable", "jsonFile"...)
	 */
	public function getSerializableProperties($pSerializationType) {
		$lProperties = array();
		foreach ($this->mProperties as $lPropertyName => $lProperty) {
			if (($lProperty instanceof ForeignProperty) && $lProperty->hasSerializationUnit($pSerializationType)) {
				$lProperties[] = $lProperty;
			}
		}
		return $lProperties;
	}
	
	public function getIdProperties() {
		return $this->mIds;
	}
	
	public function hasUniqueIdProperty() {
		return count($this->mIds) == 1;
	}
	
	public function hasIdProperty() {
		return !empty($this->mIds);
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
	 * @return CompositionProperty:
	 */
	public function getCompositions() {
		return $this->mCompositions;
	}
	
	public function getSerializationIds() {
		$lSerializationIds = array();
		foreach ($this->mIds as $lIdPropertyName) {
			$lSerializationIds[] = $this->getProperty($lIdPropertyName)->getSerializationName();
		}
		return $lSerializationIds;
	}
	
	public function getFirstIdPropertyName() {
		return empty($this->mIds) ? null : $this->mIds[0];
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
	
	public function getEscapedDbColumns() {
		return $this->mEscapedDbColumn;
	}
	
	/**
	 * @param array $pIdValues encode id in json format
	 */
	public function encodeId($pIdValues) {
		if (empty($pIdValues)) {
			return null;
		}
		$i = 0;
		foreach ($this->getIdProperties() as $lPropertyName) {
			if (!is_null($pIdValues[$i]) && !$this->getPropertyModel($lPropertyName)->isCheckedValueType($pIdValues[$i])) {
				$pIdValues[$i] = $this->getPropertyModel($lPropertyName)->castValue($pIdValues[$i]);
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
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @return LocalObjectCollection
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
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if ($pObject->getModel()->hasProperty($lPropertyName)) {
				$lProperty = $pObject->getModel()->getProperty($lPropertyName);
				if (($pPrivate || !$lProperty->isPrivate()) && (!$pUseSerializationName || $lProperty->isSerializable())) {
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					$lReturn->$lName = $lProperty->getModel()->_toStdObject($lValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
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
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if ($pObject->getModel()->hasProperty($lPropertyName)) {
				$lProperty =  $pObject->getModel()->getProperty($lPropertyName);
				if (($pPrivate || !$lProperty->isPrivate()) && (!$pUseSerializationName || $lProperty->isSerializable())) {
					$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
					if (($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum)){
						$pXmlNode[$lName] = $lProperty->getModel()->_toXml($lValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
					} else {
						$pXmlChildNode = $pXmlNode->addChild($lName);
						$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
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
		$this->_addMainCurrentObject($pObject, $pMainForeignObjects);
		$lArray = $this->_toFlattenedArray($pObject, $pPrivate, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
		$this->_removeMainCurrentObject($pObject, $pMainForeignObjects);
		self::$sInstanceObjectHash = [];
		return $lArray;
	}
		
	protected function _toFlattenedArray(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lStdObject   = $this->_toStdObject($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		$lMapOfString = $this->stdObjectToFlattenedArray($lStdObject, $pObject->getModel(), $pUseSerializationName);
		
		if (is_array($pMainForeignObjects)) {
			foreach ($pMainForeignObjects as $lMainModelName => $lValues) {
				$lModel = InstanceModel::getInstance()->getInstanceModel($lMainModelName);
				foreach ($pMainForeignObjects[$lMainModelName] as $lId => &$lValue) {
					// when we come from ModelArray::toFlattenedArray() we can pass several times in this function
					// so some values can be already transformed so we must transform only object values
					if (is_object($lValue)) {
						$lValue = $this->stdObjectToFlattenedArray($lValue, $lModel, $pUseSerializationName);
					}
				}
			}
		}
		return $lMapOfString;
	}
	
	/**
	 * transform an stdClass to an array which each stdclass or array values are transformed to string
	 * @param \stdClass $pObject
	 * @param Model $pModel
	 * @param boolean $pUseSerializationName
	 */
	public function stdObjectToFlattenedArray($pStdObject, $pModel, $pUseSerializationName) {
		$lMapOfString = array();
		foreach ($pModel->getProperties() as $lProperty) {
			$lPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
			if (!$lProperty->isComposition() && isset($pStdObject->$lPropertyName)) {
				$lValue = $pStdObject->$lPropertyName;
				if ($lProperty->isForeign() && is_object($lValue) && $lProperty->hasSqlTableUnit()) {
					$lValue = $lValue->id;
				} else if (is_object($lValue) || is_array($lValue)) {
					$lValue = json_encode($lValue);
				}
				$lMapOfString[$lPropertyName] = $lValue;
			}
		}
		return $lMapOfString;
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
		$lObject = $this->_getOrCreateObjectInstanceFromObject($pStdObject, $pUseSerializationName, $pLocalObjectCollection);
		$this->_fillObjectFromStdObject($lObject, $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	protected function _fillObjectFromStdObject(Object $pObject, \stdClass $pStdObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		foreach ($lModel->getProperties() as $lPropertyName => $lProperty) {
			if ($pPrivate || !$lProperty->isPrivate()) {
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
	}
	
	protected function _fromXml($pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromXml($pXml, $pUseSerializationName, $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection)) ? $lObject : null;
	}
	
	protected function _fillObjectFromXml(Object $pObject, \SimpleXMLElement $pXml, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lHasValue = false;
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		foreach ($lModel->getProperties() as $lPropertyName => $lProperty) {
			if ($pPrivate || !$lProperty->isPrivate()) {
				$lXmlPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lPropertyName;
				if (($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum)) {
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
		return $lHasValue;
	}
	
	protected function _fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow,$pUseSerializationName, $pLocalObjectCollection);
		$this->_fillObjectFromFlattenedArray($lObject, $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	
	public function _fillObjectFromFlattenedArray(Object $pObject, array $pRow, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lModel = $pObject->getModel();
		if ($lModel !== $this && !$lModel->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		foreach ($lModel->getProperties() as $lPropertyName => $lProperty) {
			if ($pPrivate || !$lProperty->isPrivate()) {
				$lFlattenedPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lPropertyName;
				if (array_key_exists($lFlattenedPropertyName, $pRow)) {
					if (is_null($pRow[$lFlattenedPropertyName])) {
						continue;
					}
					$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromFlattenedValue($pRow[$lFlattenedPropertyName], $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection));
				}
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
	
	protected function _fromObjectId($pValue, $pLocalObjectCollection) {
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
			$lIdProperties = $this->getIdProperties();
			if (count($lIdProperties) == 1) {
				$pObject->setValue($lIdProperties[0], $pId);
			} else {
				$lIdValues = $this->decodeId($pId);
				foreach ($this->getIdProperties() as $lIndex => $lPropertyName) {
					if (!is_null($lIdValues[$lIndex])) {
						$pObject->setValue($lPropertyName, $lIdValues[$lIndex]);
					}
				}
			}
		}
		return $pObject;
	}
	
	public function getIdFromStdObject($pStdObject, $pUseSerializationName) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperties[0], true)->getSerializationName() : $this->getProperty($lIdProperties[0], true)->getName();
			return isset($pStdObject->$lPropertyName) ? $this->getPropertyModel($lIdProperties[0])->_fromStdObject($pStdObject->$lPropertyName) : null;
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperty, true)->getSerializationName() : $this->getProperty($lIdProperty, true)->getName();
			if (isset($pStdObject->$lPropertyName)) {
				$lIdValues[] = $this->getPropertyModel($lIdProperty)->_fromStdObject($pStdObject->$lPropertyName);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromXml($pXml, $pUseSerializationName) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperties[0], true)->getSerializationName() : $this->getProperty($lIdProperties[0], true)->getName();
			return isset($pXml[$lPropertyName]) ? $this->getPropertyModel($lIdProperties[0])->_fromXml($pXml[$lPropertyName]) : null;
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperty, true)->getSerializationName() : $this->getProperty($lIdProperty, true)->getName();
			if (isset($pXml[$lPropertyName])) {
				$lIdValues[] = $this->getPropertyModel($lIdProperty)->_fromXml($pXml[$lPropertyName]);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromFlattenedArray($pRow, $pUseSerializationName) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperties[0], true)->getSerializationName() : $this->getProperty($lIdProperties[0], true)->getName();
			return isset($pRow[$lPropertyName]) ? $this->getPropertyModel($lIdProperties[0])->_fromFlattenedValue($pRow[$lPropertyName]) : null;
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			$lPropertyName = $pUseSerializationName ? $this->getProperty($lIdProperty, true)->getSerializationName() : $this->getProperty($lIdProperty, true)->getName();
			if (isset($pRow[$lPropertyName])) {
				$lIdValues[] = $this->getPropertyModel($lIdProperty)->_fromFlattenedValue($pRow[$lPropertyName]);
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