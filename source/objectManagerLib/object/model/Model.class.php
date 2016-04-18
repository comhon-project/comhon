<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\exception\PropertyException;
use \stdClass;

abstract class Model {
	
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
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstance($pId, $pLocalObjectCollection, $pIsloaded = true) {
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
		return count($this->mIds) > 0;
	}
	
	public function getSerializationIds() {
		$lSerializationIds = array();
		foreach ($this->mIds as $lIdPropertyName) {
			$lSerializationIds[] = $this->getProperty($lIdPropertyName)->getSerializationName();
		}
		return $lSerializationIds;
	}
	
	public function getFirstId() {
		return count($this->mIds) > 0 ? $this->mIds[0] : null;
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
	 * 
	 * @param array $pIdValues
	 */
	public function formatId($pIdValues) {
		return count($pIdValues) > 0 ? implode("-", $pIdValues) : null;
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
	
	public function toSqlDataBase($pObject, $pTable, $pPDO) {
		$lQueryColumns = array();
		$lQueryValues = array();
		
		foreach ($pObject->getValues() as $lPropertyName => $lValue) {
			if ($this->hasProperty($lPropertyName) && $lProperty->hasSerializationReturn()) {
				$lProperty =  $this->getProperty($lPropertyName);
				$lQueryColumns[] = $lProperty->getSerializationName();
				$lParams[] = $lProperty->save($lValue, true);
				$lQueryValues[] = "?";
			}
		}
		$lQuery = "INSERT INTO ".$pTable." (".implode(", ", $lQueryColumns).") VALUES (".implode(", ", $lQueryValues).");";
		$pPDO->prepareQuery($lQuery, $lParams);
		trigger_error(var_export($lQuery, true));
		
		return $pPDO->doQuery($lQuery);
	}
	
	public function toId($pObject, $pUseSerializationName = false) {
		$lId = $pObject->getId();
		if (is_null($lId)) {
			trigger_error("Warning cannot export foreign property with model '{$this->mModelName}' because this model doesn't have id");
		}
		return $lId;
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
		list($lObject, $lLocalObjectCollection) = $this->_getOrCreateObjectInstance($this->getIdFromPhpObject($pPhpObject), $pLocalObjectCollection);
		$this->_fillObjectFromPhpObject($lObject, $pPhpObject, $lLocalObjectCollection);
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
		list($lObject, $lLocalObjectCollection) = $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml), $pLocalObjectCollection);
		return $this->_fillObjectFromXml($lObject, $pXml, $lLocalObjectCollection) ? $lObject : null;
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
		list($lObject, $lLocalObjectCollection) = $this->_getOrCreateObjectInstance($this->getIdFromSqlDatabase($pRow), $pLocalObjectCollection);
		$this->_fillObjectFromSqlDatabase($lObject, $pRow, $lLocalObjectCollection, $pAddUnloadValues);
		return $lObject;
	}
	
	public function _fillObjectFromSqlDatabase($pObject, $pRow, $pLocalObjectCollection, $pAddUnloadValues = true) {
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
		if (count($lIdProperties = $this->getIdProperties()) != 1) {
			throw new \Exception("model '{$this->mModelName}' must have one and only one id");
		}
		list($lObject, $lObjectCollectionKey) = $this->_getOrCreateObjectInstance($pId, $pLocalObjectCollection, false, false);
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
		return $this->formatId($lIdValues);
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
		return $this->formatId($lIdValues);
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
		return $this->formatId($lIdValues);
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1->isEqual($pValue2);
	}
}