<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\object\object\Config;
use \stdClass;

abstract class Model {

	const MERGE     = 'merge';
	const OVERWRITE = 'overwrite';
	const NO_MERGE  = 'no_merge';
	
	protected static $sInstanceObjectHash = array();

	protected $mModelName;
	protected $mIsLoaded     = false;
	protected $mIsLoading    = false;
	
	private $mProperties;
	private $mObjectClass    = "objectManagerLib\object\object\Object";
	private $mIds            = array();
	
	
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
		if (is_null($this->mProperties)) {
			trigger_error($this->mModelName);
			trigger_error(var_export($this->isLoaded(), true));
			$lNodes = debug_backtrace();
			for ($i = 0; $i < count($lNodes); $i++) {
				trigger_error("$i. ".basename($lNodes[$i]['file']) ." : " .$lNodes[$i]['function'] ."(" .$lNodes[$i]['line'].")");
			}
		}
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
	
	public function getSerialization() {
		return null;
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
	public function _encodeId($pIdValues) {
		return empty($pIdValues) ? null : json_encode($pIdValues);
	}
	
	/**
	 * @param string $pId decode id from json format
	 */
	public function _decodeId($pId) {
		return json_decode($pId);
	}
	
	/**
	 * @param Object $pObject
	 */
	public function encodeIdfromObject(Object $pObject) {
		$lValues = array();
		foreach ($this->getIdProperties() as $lPropertyName) {
			$lValues[] = $pObject->getValue($lPropertyName);
		}
		return $this->_encodeId($lValues);
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @return LocalObjectCollection
	 */
	private function _getLocalObjectCollection($pObject, $pLocalObjectCollection) {
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
	public function toObject($pObject, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lReturn = new stdClass();
		if (is_null($pObject)) {
			return null;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				return $this->toObjectId($pObject, $pUseSerializationName);
			}
		} else {
			self::$sInstanceObjectHash[spl_object_hash($pObject)] = 0;
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]++;
		foreach ($pObject->getValues() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$lProperty =  $this->getProperty($lKey);
				$lName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
				$lReturn->$lName = $lProperty->getModel()->toObject($lValue, $pUseSerializationName, $pMainForeignObjects);
			}
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
		return $lReturn;
	}
	
	public function toObjectId($pObject, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->toId($pObject, $pUseSerializationName);
	}
	
	public function toXml($pObject, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pObject)) {
			return;
		}
		if (array_key_exists(spl_object_hash($pObject), self::$sInstanceObjectHash)) {
			if (self::$sInstanceObjectHash[spl_object_hash($pObject)] > 0) {
				trigger_error("Warning loop detected. Object '{$pObject->getModel()->getModelName()}' can't be exported");
				$this->toXmlId($pObject, $pXmlNode, $pUseSerializationName);
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
					$pXmlNode[$lName] = $lProperty->getModel()->toXml($lValue, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
				} else {
					$pXmlChildNode = $pXmlNode->addChild($lName);
					$lProperty->getModel()->toXml($lValue, $pXmlChildNode, $pUseSerializationName, $pMainForeignObjects);
				}
			}
		}
		self::$sInstanceObjectHash[spl_object_hash($pObject)]--;
	}
	
	public function toXmlId($pObject, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lDomNode  = dom_import_simplexml($pXmlNode);
		$lId       = $this->toId($pObject, $pUseSerializationName);
		$lTextNode = new \DOMText($lId);
		$lDomNode->appendChild($lTextNode);
		return $lId;
	}
	
	public function toSqlDataBase($pObject, $pUseSerializationName = true, &$pMainForeignObjects = null) {
		$lMapOfString      = array();
		$lDatabaseTimezone = Config::getInstance()->getValue('database')->getValue('timezone');
		$lDefaultTimeZone  = false;
		
		if (date_default_timezone_get() != $lDatabaseTimezone) {
			$lDefaultTimeZone = date_default_timezone_get();
			date_default_timezone_set($lDatabaseTimezone);
		}
		
		$lPhpObject = $this->toObject($pObject, $pUseSerializationName, $pMainForeignObjects);
		$lMapOfString = $this->objectToSqlArrayString($lPhpObject, $this, $pUseSerializationName);
		
		if (is_array($pMainForeignObjects)) {
			foreach ($pMainForeignObjects as $lMainModelName => &$lValues) {
				$lModel = InstanceModel::getInstance()->getInstanceModel($lMainModelName);
				foreach ($pMainForeignObjects as $lId => $lValue) {
					$lValues[$lId] = $this->objectToSqlArrayString($lPhpObject, $lModel, $pUseSerializationName);
				}
			}
		}
		if ($lDefaultTimeZone) {
			date_default_timezone_set($lDefaultTimeZone);
		}
		return $lMapOfString;
	}
	
	/**
	 * transform an stdClass to an array which each stdclass or array values are transformed to string
	 * @param \stdClass $pObject
	 */
	public function objectToSqlArrayString($pPhpObject, $pModel, $pUseSerializationName) {
		$lMapOfString = array();
		foreach ($pModel->getProperties() as $lProperty) {
			$lPropertyName = $pUseSerializationName ? $lProperty->getSerializationName() : $lProperty->getName();
			if (!$lProperty->isComposition() && isset($pPhpObject->$lPropertyName)) {
				$lValue = $pPhpObject->$lPropertyName;
				if (is_object($lValue)) {
					$lValue = json_encode($lValue);
				}
				$lMapOfString[$lPropertyName] = $lValue;
			}
		}
		return $lMapOfString;
	}
	
	public function toId($pObject, $pUseSerializationName = false) {
		if ($pObject->hasCompleteId()) {
			return $pObject->getId();
		} else {
			trigger_error("Warning cannot export id of foreign property with model '{$this->mModelName}' because object doesn't have complete id");
			return null;
		}
	}
	
	public function fillObjectFromPhpObject($pObject, $pPhpObject) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromXml($pObject, $pXml) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	public function fillObjectFromSqlDatabase($pObject, $pRow, $pAddUnloadValues = true) {
		throw new \Exception('can\'t apply function. Only callable for MainModel');
	}
	
	protected function _fromObject($pPhpObject, $pLocalObjectCollection) {
		if (is_null($pPhpObject)) {
			return null;
		}
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromPhpObject($pPhpObject), $pLocalObjectCollection);
		$this->_fillObjectFromPhpObject($lObject, $pPhpObject, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection));
		return $lObject;
	}
	
	protected function _fillObjectFromPhpObject($pObject, $pPhpObject, $pLocalObjectCollection) {
		if (is_null($pPhpObject)) {
			return null;
		}
		foreach ($pPhpObject as $lKey => $lPhpValue) {
			if ($this->hasProperty($lKey)) {
				$pObject->setValue($lKey, $this->getPropertyModel($lKey)->_fromObject($lPhpValue, $pLocalObjectCollection));
			}
		}
	}
	
	protected function _fromXml($pXml, $pLocalObjectCollection) {
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml), $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection)) ? $lObject : null;
	}
	
	protected function _fillObjectFromXml($pObject, $pXml, $pLocalObjectCollection) {
		$lHasValue = false;
		foreach ($pXml->attributes() as $lKey => $lValue) {
			if ($this->hasProperty($lKey)) {
				$pObject->setValue($lKey,  $this->getPropertyModel($lKey)->_fromXml($lValue, $pLocalObjectCollection));
				$lHasValue = true;
			}
		}
		foreach ($pXml->children() as $lChild) {
			$lPropertyName = $lChild->getName();
			if ($this->hasProperty($lPropertyName)) {
				$pObject->setValue($lPropertyName, $this->getPropertyModel($lPropertyName)->_fromXml($lChild, $pLocalObjectCollection));
				$lHasValue = true;
			}
		}
		return $lHasValue;
	}
	
	protected function _fromSqlDataBase($pRow, $pLocalObjectCollection, $pAddUnloadValues = true) {
		$lObject = $this->_getOrCreateObjectInstance($this->getIdFromSqlDatabase($pRow), $pLocalObjectCollection);
		$this->_fillObjectFromSqlDatabase($lObject, $pRow, $this->_getLocalObjectCollection($lObject, $pLocalObjectCollection), $pAddUnloadValues);
		return $lObject;
	}
	
	public function _fillObjectFromSqlDatabase($pObject, $pRow, $pLocalObjectCollection, $pAddUnloadValues = true) {
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
				$pObject->setValue($lPropertyName, $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()], $pLocalObjectCollection));
			}
			else if ($pAddUnloadValues && ($lProperty instanceof ForeignProperty) && !is_null($lProperty->hasSqlTableUnit())) {
				$pObject->initValue($lPropertyName, false);
			}
		}
		
		if ($lDefaultTimeZone) {
			date_default_timezone_set($lDefaultTimeZone);
		}
	}
	
	protected function _fromSqlColumn($pJsonEncodedObject, $pLocalObjectCollection) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lPhpObject = json_decode($pJsonEncodedObject);
		return $this->_fromObject($lPhpObject, $pLocalObjectCollection);
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
	
	protected function fromSqlColumnId($pValue) {
		return $this->_fromId($pValue);
	}
	
	protected function _fromId($pId, $pLocalObjectCollection = null) {
		if (is_object($pId) || $pId == '') {
			$pId = is_object($pId) ? json_encode($pId) : $pId;
			throw new \Exception("malformed id '$pId' for model '{$this->mModelName}'");
		}
		if (is_null($pId)) {
			return null;
		}
		return $this->_getOrCreateObjectInstance($pId, $pLocalObjectCollection, false, false);
	}
	
	protected function _buildObjectFromId($pId, $pIsloaded) {
		$lObject = $this->getObjectInstance($pIsloaded);
		if (!is_null($pId)) {
			$lIdProperties = $this->getIdProperties();
			if (count($lIdProperties) == 1) {
				$lObject->setValue($lIdProperties[0], $pId);
			} else {
				$lIdValues = $this->_decodeId($pId);
				foreach ($this->getIdProperties() as $lIndex => $lPropertyName) {
					$lObject->setValue($lPropertyName, $lIdValues[$lIndex]);
				}
			}
		}
		return $lObject;
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
		return $this->_encodeId($lIdValues);
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
		return $this->_encodeId($lIdValues);
	}
	
	public function getIdFromSqlDatabase($pRow) {
		$lIdProperties = $this->getIdProperties();
		if (count($lIdProperties) == 1) {
			$lProperty = $this->getProperty($lIdProperties[0]);
			return $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()]);
		}
		$lIdValues = [];
		foreach ($lIdProperties as $lIdProperty) {
			if (isset($pRow[$lIdProperty])) {
				$lProperty   = $this->getProperty($lIdProperty);
				$lIdValues[] = $lProperty->getModel()->_fromSqlColumn($pRow[$lProperty->getSerializationName()]);
			} else {
				$lIdValues[] = null;
			}
		}
		return $this->_encodeId($lIdValues);
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1->isEqual($pValue2);
	}
}