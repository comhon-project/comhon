<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\MainObjectCollection;
use objectManagerLib\object\LocalObjectCollection;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\visitor\ObjectCollectionCreator;
use \stdClass;
use objectManagerLib\object\object\SerializationUnit;

class MainModel extends Model {
	
	private $mSerialization            = null;
	private $mSerializationInitialised = false;
	
	protected final function _setSerialization() {
		if (!$this->mSerializationInitialised) {
			$this->mSerialization = InstanceModel::getInstance()->getSerialization($this);
			$this->mSerializationInitialised = true;
		}
	}
	
	public function hasLoadedSerialization() {
		return $this->mSerializationInitialised;
	}
	
	/**
	 * @return SerializationUnit
	 */
	public function getSerialization() {
		return $this->mSerialization;
	}
	
	public function hasSerialization() {
		return !is_null($this->mSerialization);
	}
	
	public function hasSqlTableUnit() {
		return !is_null($this->mSerialization) && ($this->mSerialization instanceof SqlTable);
	}
	
	public function getSqlTableUnit() {
		return !is_null($this->mSerialization) && ($this->mSerialization instanceof SqlTable) ? $this->mSerialization : null;
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return !is_null($this->mSerialization) && ($this->mSerialization->getModel()->getModelName() == $pSerializationType);
	}
	
	public function fromObject($pPhpObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromObject($pPhpObject, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstance($this->getIdFromPhpObject($pPhpObject), null);
				$lObject->resetValues();
				$this->_fillObjectFromPhpObject($lObject, $pPhpObject, $lDateTimeZone, new LocalObjectCollection());
				break;
			case self::NO_MERGE:
				$lObject = $this->getObjectInstance();
				$this->_fillObjectFromPhpObject($lObject, $pPhpObject, $lDateTimeZone, new LocalObjectCollection());
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromXml($pXml, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstance($this->getIdFromXml($pXml), null);
				$lObject->resetValues();
				$this->_fillObjectFromXml($lObject, $pXml, $lDateTimeZone, new LocalObjectCollection());
				break;
			case self::NO_MERGE:
				$lObject = $this->getObjectInstance();
				$this->_fillObjectFromXml($lObject, $pXml, $lDateTimeZone, new LocalObjectCollection());
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromSqlDataBase($pRow, $pMergeType = self::MERGE, $pTimeZone = null, $pAddUnloadValues = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromSqlDataBase($pRow, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstance($this->getIdFromSqlDatabase($pRow), null);
				$lObject->resetValues();
				$this->_fillObjectFromSqlDatabase($lObject, $pRow, $lDateTimeZone, new LocalObjectCollection());
				break;
			case self::NO_MERGE:
				$lObject = $this->getObjectInstance();
				$this->_fillObjectFromSqlDatabase($lObject, $pRow, $lDateTimeZone, new LocalObjectCollection());
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromSqlDataBaseId($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lId = $this->getIdFromSqlDatabase($pRow);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_getOrCreateObjectInstance($lId, null, false, false);
				break;
			case self::OVERWRITE:
				$lAlreadyExists = !is_null(MainObjectCollection::getInstance()->getObject($lId, $this->mModelName));
				$lObject = $this->_getOrCreateObjectInstance($lId, null, false, false);
				if ($lAlreadyExists) {
					$lObject->resetValues();
					$this->_fillObjectwithId($lObject, $lId);
					$lObject->setUnLoadStatus();
				}
				break;
			case self::NO_MERGE:
				$lObject = $this->_buildObjectFromId($lId, false);
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fillObjectFromPhpObject(Object $pObject, $pPhpObject, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromPhpObject($pObject, $pPhpObject, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromXml($pObject, $pXml, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectFromSqlDatabase(Object $pObject, $pRow, $pTimeZone = null, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromSqlDatabase($pObject, $pRow, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject), $pAddUnloadValues);
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function toObjectId(Object $pObject, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toObjectId($pObject, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toObjectId(Object $pObject, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lId = parent::_toObjectId($pObject, $pUseSerializationName, $pMainForeignObjects);
		if (is_array($pMainForeignObjects) && !(array_key_exists($this->mModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$this->mModelName]))) {
			$pMainForeignObjects[$this->mModelName][$lId] = $this->_toObject($pObject, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		}
		return $lId;
	}
	
	public function toXmlId(Object $pObject, $pXmlNode, $pUseSerializationName = false, $pTimeZone = null, &$pMainForeignObjects = null) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		return $this->_toXmlId($pObject, $pXmlNode, $pUseSerializationName, new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone), $pMainForeignObjects);
	}
		
	protected function _toXmlId(Object $pObject, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lId = parent::_toXmlId($pObject, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
		if (is_array($pMainForeignObjects) && !(array_key_exists($this->mModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$this->mModelName]))) {
			$lXmlNode = new \SimpleXmlElement("<{$this->getModelName()}/>");
			$this->_toXml($pObject, $lXmlNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
			$pMainForeignObjects[$this->mModelName][$lId] = $lXmlNode;
		}
	}
	
	/**
	 * load serialized object 
	 * @param string|integer $pId
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return Object|null null if load is unsuccessfull
	 */
	public function loadObject($pId, $pForceLoad = false) {
		$this->load();
		if (!$this->hasIdProperty()) {
			throw new \Exception("model must have at least one id property");
		}
		$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
		
		if (is_null($lMainObject)) {
			$lMainObject = $this->_buildObjectFromId($pId, false);
		} else if (!$pForceLoad) {
			return $lMainObject;
		}

		return $this->loadAndFillObject($lMainObject, $pForceLoad) ? $lMainObject : null;
	}
	
	/**
	 * load instancied object with serialized object
	 * @param Object $pObject
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return Object|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(Object $pObject, $pForceLoad = false) {
		$lSuccess = false;
		$this->load();
		if (is_null($lSerializationUnit = $this->getSerialization())) {
			throw new \Exception("model doesn't have serialization");
		}
		if (!$pObject->isLoaded() || $pForceLoad) {
			$lSuccess = $lSerializationUnit->loadObject($pObject);
		}
		return $lSuccess;
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string|integer $pLocalObjectCollection not used but we need to have it to match with LocalModel
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @return array [Object,string] second element is the key in ObjectCollection where we can found Object returned
	 */
	protected function _getOrCreateObjectInstance($pId, $pLocalObjectCollection = null, $pIsloaded = true, $pUpdateLoadStatus = true) {
		if (!$this->hasIdProperty()) {
			$lMainObject = $this->getObjectInstance($pIsloaded);
			MainObjectCollection::getInstance()->addObject($lMainObject);
			//trigger_error("new main without id $pId, $this->mModelName");
		}
		else {
			$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $this->_buildObjectFromId($pId, $pIsloaded);
				MainObjectCollection::getInstance()->addObject($lMainObject);
				//trigger_error("new main $pId, $this->mModelName");
			}
			else if ($pUpdateLoadStatus) {
				//trigger_error("main already added $pId, $this->mModelName");
				//trigger_error("update main status ".var_export($lMainObject->isLoaded(), true));
				$lMainObject->setLoadStatus();
			}
			else {
				//trigger_error("main already added $pId, $this->mModelName doesn't update");
			}
		}
		return $lMainObject;
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @return LocalObjectCollection
	 */
	protected function _getLocalObjectCollection($pObject, $pLocalObjectCollection) {
		return $this->_loadLocalObjectCollection($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return LocalObjectCollection
	 */
	private function _loadLocalObjectCollection($pObject) {
		$lObjectCollectionCreator = new ObjectCollectionCreator();
		return $lObjectCollectionCreator->execute($pObject);
	}
	
}