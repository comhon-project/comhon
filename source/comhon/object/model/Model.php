<?php
namespace comhon\object\model;

use comhon\object\singleton\InstanceModel;
use comhon\object\object\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\exception\PropertyException;
use comhon\object\object\Config;
use \stdClass;
use comhon\utils\Utils;

abstract class Model {

	const MERGE     = 0;
	const OVERWRITE = 1;
	const NO_MERGE  = 2;
	
	protected static $sInstanceObjectHash = array();

	protected $mModelName;
	protected $mIsLoaded     = false;
	protected $mIsLoading    = false;
	
	private $mProperties;
	private $mObjectClass     = "comhon\object\object\Object";
	private $mIds             = array();
	private $mEscapedDbColumn = array();
	
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
	
	public function getObjectInstance($pIsloaded = true) {
		return new $this->mObjectClass($this, $pIsloaded);
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string|integer $pLocalObjectCollection not used but we need to have it to match with LocalModel
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status 
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstance($pId, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel or LocalModel');
	}
	
	public function getModelName() {
		return $this->mModelName;
	}
	
	public function getMainModelName() {
		return $this->mModelName;
	}
	
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
	
	public function getEscapedDbColumns() {
		return $this->mEscapedDbColumn;
	}
	
	/*
	 * return true if the object is a new object and doesn't exist in database
	 */
	public function isNew() {
		//TODO
		return true;
	}
	
	/**
	 * @param array $pIdValues encode id in json format
	 */
	public function encodeId($pIdValues) {
		return empty($pIdValues) ? null : json_encode($pIdValues);
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
	
	/**
	 * 
	 * @param Object $pObject
	 * @param boolean $pUseSerializationName
	 * @param array|null $pMainForeignObjects 
	 * by default foreign properties with MainModel are not exported 
	 * but you can export them by spsifying an array in third parameter
	 * @return NULL|\stdClass
	 */
	public function toObject(Object $pObject, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toObject($pObject, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toObject(Object $pObject, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = new stdClass();
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				return $this->_toObjectId($pObject, $pUseSerializationName, $pDateTimeZone);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		foreach ($pObject->getValues() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lProperty =  $this->getProperty($lKey);
				$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
				$lReturn->$lName = $lProperty->getModel()->_toObject($lValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
			}
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	public function toObjectId(Object $pObject, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toObjectId($pObject, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toObjectId(Object $pObject, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->_toId($pObject, $pUseSerializationName);
	}
	
	public function toXml(Object $pObject, $pXmlNode, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toXml($pObject, $pXmlNode, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toXml(Object $pObject, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pObject)) {
			return;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				$this->_toXmlId($pObject, $pXmlNode, $pUseSerializationName, $pDateTimeZone);
				return;
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		foreach ($pObject->getValues() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lProperty =  $this->getProperty($lKey);
				$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
				if (($lProperty->getModel() instanceof SimpleModel) || ($lProperty->getModel() instanceof ModelEnum)){
					$pXmlNode[$lName] = $lProperty->getModel()->_toXml($lValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				} else {
					$pXmlChildNode = $pXmlNode->addChild($lName);
					$lProperty->getModel()->_toXml($lValue, $pXmlChildNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
	}
	
	public function toXmlId(Object $pObject, $pXmlNode, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toXmlId($pObject, $pXmlNode, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toXmlId(Object $pObject, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lDomNode  = dom_import_simplexml($pXmlNode);
		$lId       = $this->_toId($pObject, $pUseSerializationName);
		$lTextNode = new \DOMText($lId);
		$lDomNode->appendChild($lTextNode);
		return $lId;
	}
	
	public function toSqlDataBase(Object $pObject, $pUseSerializationName = true, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toSqlDataBase($pObject, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toSqlDataBase(Object $pObject, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lPhpObject   = $this->_toObject($pObject, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		$lMapOfString = $this->objectToSqlArrayString($lPhpObject, $this, $pUseSerializationName);
		
		if (is_array($pMainForeignObjects)) {
			foreach ($pMainForeignObjects as $lMainModelName => &$lValues) {
				$lModel = InstanceModel::getInstance()->getInstanceModel($lMainModelName);
				foreach ($pMainForeignObjects as $lId => $lValue) {
					$lValues[$lId] = $this->objectToSqlArrayString($lPhpObject, $lModel, $pUseSerializationName);
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
	public function objectToSqlArrayString($pPhpObject, $pModel, $pUseSerializationName) {
		$lMapOfString = array();
		foreach ($pModel->getProperties() as $lProperty) {
			$lPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
			if (!$lProperty->isComposition() && isset($pPhpObject->$lPropertyName)) {
				$lValue = $pPhpObject->$lPropertyName;
				if (is_object($lValue) || is_array($lValue)) {
					$lValue = json_encode($lValue);
				}
				$lMapOfString[$lPropertyName] = $lValue;
			}
		}
		return $lMapOfString;
	}
	
	public function _toId(Object $pObject, $pUseSerializationName = false) {
		if ($pObject->hasCompleteId()) {
			return $pObject->getId();
		} else {
			trigger_error("Warning cannot export id of foreign property with model '{$this->mModelName}' because object doesn't have complete id");
			return null;
		}
	}
	
	public function fillObjectFromPhpObject(Object $pObject, $pPhpObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromSqlDatabase(Object $pObject, $pRow, $pTimeZone = null, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	protected function _fromObject($pPhpObject, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pPhpObject)) {
			return null;
		}
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromPhpObject($pPhpObject), $pLocalObjectCollection);
		$this->_fillObjectFromPhpObject($lObject, $pPhpObject, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	protected function _fillObjectFromPhpObject(Object $pObject, $pPhpObject, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pPhpObject)) {
			return null;
		}
		foreach ($pPhpObject as $lKey => $lPhpValue) {
			if ($this->hasProperty($lKey)) {
				$pObject->setValue($lKey, $this->getPropertyModel($lKey)->_fromObject($lPhpValue, $pDateTimeZone, $pLocalObjectCollection));
			}
		}
	}
	
	protected function _fromXml($pXml, $pDateTimeZone, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml), $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection)) ? $lObject : null;
	}
	
	protected function _fillObjectFromXml(Object $pObject, $pXml, $pDateTimeZone, $pLocalObjectCollection) {
		$lHasValue = false;
		foreach ($pXml->attributes() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$pObject->setValue($lKey,  $this->getPropertyModel($lKey)->_fromXml($lValue, $pDateTimeZone, $pLocalObjectCollection));
				$lHasValue = true;
			}
		}
		foreach ($pXml->children() as $lChild) {
			$lPropertyName = $lChild->getName();
			if ($this->hasProperty($lPropertyName)) {
				$pObject->setValue($lPropertyName, $this->getPropertyModel($lPropertyName)->_fromXml($lChild, $pDateTimeZone, $pLocalObjectCollection));
				$lHasValue = true;
			}
		}
		return $lHasValue;
	}
	
	protected function _fromSqlDataBase($pRow, $pDateTimeZone, $pLocalObjectCollection, $pAddUnloadValues = true) {
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromSqlDatabase($pRow), $pLocalObjectCollection);
		$this->_fillObjectFromSqlDatabase($lObject, $pRow, $pDateTimeZone, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection), $pAddUnloadValues);
		return $lObject;
	}
	
	public function _fillObjectFromSqlDatabase(Object $pObject, $pRow, $pDateTimeZone, $pLocalObjectCollection, $pAddUnloadValues = true) {
		$lDatabaseTimezone = Config::getInstance()->getValue('database')->getValue('timezone');
		$lDefaultTimeZone  = false;
		
		if (date_default_timezone_get() != $lDatabaseTimezone) {
			$lDefaultTimeZone = date_default_timezone_get();
			date_default_timezone_set($lDatabaseTimezone);
		}
		
		foreach ($this->getProperties() as $lPropertyName => $lProperty) {
			if (array_key_exists($lProperty->getSerializationName(), $pRow)) {
				if (is_null($pRow[$lProperty->getSerializationName()])) {
					continue;
				}
				$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()], $pDateTimeZone, $pLocalObjectCollection));
			}
			else if ($pAddUnloadValues && ($lProperty instanceof ForeignProperty) && !is_null($lProperty->hasSqlTableUnit())) {
				$pObject->initValue($lPropertyName, false);
			}
		}
		
		if ($lDefaultTimeZone) {
			date_default_timezone_set($lDefaultTimeZone);
		}
	}
	
	protected function _fromSqlColumn($pJsonEncodedObject, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lPhpObject = json_decode($pJsonEncodedObject);
		return $this->_fromObject($lPhpObject, $pDateTimeZone, $pLocalObjectCollection);
	}
	
	protected function _fromObjectId($pValue, $pLocalObjectCollection) {
		return $this->_fromId($pValue, $pLocalObjectCollection);
	}
	
	protected function _fromXmlId($pValue, $pLocalObjectCollection) {
		$lId = (string) $pValue;
		if ($lId == '') {
			return null;
		}
		return $this->_fromId($lId, $pLocalObjectCollection);
	}
	
	protected function _fromSqlColumnId($pValue, $pLocalObjectCollection) {
		return $this->_fromId($pValue, $pLocalObjectCollection);
	}
	
	protected function _fromId($pId, $pLocalObjectCollection = null) {
		if (is_object($pId) || $pId === '') {
			$pId = is_object($pId) ? json_encode($pId) : $pId;
			throw new \Exception("malformed id '$pId' for model '{$this->mModelName}'");
		}
		if (is_null($pId)) {
			return null;
		}

		return $this->_getOrCreateObjectInstance($pId, $pLocalObjectCollection, false, false);
	}
	
	protected function _buildObjectFromId($pId, $pIsloaded) {
		return $this->_fillObjectwithId($this->getObjectInstance($pIsloaded), $pId);
	}
	
	protected function _fillObjectwithId(Object $pObject, $pId) {
		if (!is_null($pId)) {
			$lIdProperties = $this->getIdProperties();
			if (count($lIdProperties) == 1) {
				$pObject->setValue($lIdProperties[0], $pId);
			} else {
				$lIdValues = $this->decodeId($pId);
				foreach ($this->getIdProperties() as $lIndex => $lPropertyName) {
					$pObject->setValue($lPropertyName, $lIdValues[$lIndex]);
				}
			}
		}
		return $pObject;
	}
	
	public function getIdFromPhpObject($pPhpObject) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			return $this->getPropertyModel($lIdProperties[0])->_fromObject($pPhpObject->{$lIdProperties[0]});
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			if (isset($pPhpObject->$lIdProperty)) {
				$lIdValues[] = $this->getPropertyModel($lIdProperty)->_fromObject($pPhpObject->$lIdProperty);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromXml($pXml) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			return $this->getPropertyModel($lIdProperties[0])->_fromXml($pXml[$lIdProperties[0]]);
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			if (isset($pXml[$lIdProperty])) {
				$lIdValues[] = $this->getPropertyModel($lIdProperty)->_fromXml($pXml[$lIdProperty]);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	public function getIdFromSqlDatabase($pRow) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			$lProperty = $this->getProperty($lIdProperties[0]);
			return $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()]);
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			$lProperty = $this->getProperty($lIdProperty);
			if (isset($pRow[$lProperty->getSerializationName()])) {
				$lIdValues[] = $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()]);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->encodeId($lIdValues);
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1->isEqual($pValue2);
	}
	
	/**
	 * @param Object $pValue
	 */
	public function verifValue(Object $pValue) {}
	
}