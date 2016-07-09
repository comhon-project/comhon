<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\ObjectCollection;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\visitor\ObjectCollectionCreator;
use \stdClass;

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
	
	public function fromObject($pPhpObject, $pMergeType = self::MERGE) {
		$this->load();
		if ($pMergeType == self::OVERWRITE) {
			
		}
		return $this->_fromObject($pPhpObject, null);
	}
	
	public function fromXml($pXml) {
		$this->load();
		return $this->_fromXml($pXml, null);
	}
	
	public function fromSqlDataBase($pRow, $pAddUnloadValues = true) {
		$this->load();
		return $this->_fromSqlDataBase($pRow, null, $pAddUnloadValues);
	}
	
	public function fromSqlDataBaseId($pRow) {
		$this->load();
		return $this->_getOrCreateObjectInstance($this->getIdFromSqlDatabase($pRow), null, false, false);
	}
	
	public function fillObjectFromPhpObject($pObject, $pPhpObject, $pUpdateLoadStatus = true) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromPhpObject($pObject, $pPhpObject, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectFromXml($pObject, $pXml, $pUpdateLoadStatus = true) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromXml($pObject, $pXml, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectFromSqlDatabase($pObject, $pRow, $pUpdateLoadStatus = true, $pAddUnloadValues = true) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromSqlDatabase($pObject, $pRow, $this->_loadLocalObjectCollection($pObject), $pAddUnloadValues);
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function toObjectId($pObject, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lId = parent::toObjectId($pObject, $pUseSerializationName, $pMainForeignObjects);
		if (is_array($pMainForeignObjects) && !(array_key_exists($this->mModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$this->mModelName]))) {
			$pMainForeignObjects[$this->mModelName][$lId] = $this->toObject($pObject, $pUseSerializationName, $pMainForeignObjects);
		}
		return $lId;
	}
	
	public function toXmlId($pObject, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lId = parent::toXmlId($pObject, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
		if (is_array($pMainForeignObjects) && !(array_key_exists($this->mModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$this->mModelName]))) {
			$lXmlNode = new \SimpleXmlElement("<{$this->getModelName()}/>");
			$this->toXml($pObject, $lXmlNode, $pUseSerializationName, $pMainForeignObjects);
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
		$lMainObject = ObjectCollection::getInstance()->getMainObject($pId, $this->mModelName);
		
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
		trigger_error("=========== $this->mModelName =============");
		if (!$this->hasIdProperty()) {
			$lMainObject = $this->getObjectInstance($pIsloaded);
			ObjectCollection::getInstance()->addMainObject($lMainObject);
			trigger_error("new main without id $pId, $this->mModelName");
		}
		else {
			$lMainObject = ObjectCollection::getInstance()->getMainObject($pId, $this->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $this->_buildObjectFromId($pId, $pIsloaded);
				ObjectCollection::getInstance()->addMainObject($lMainObject);
				trigger_error("new main $pId, $this->mModelName");
			}
			else if ($pUpdateLoadStatus) {
				trigger_error("main already added $pId, $this->mModelName");
				trigger_error("update main status ".var_export($lMainObject->isLoaded(), true));
				$lMainObject->setLoadStatus();
			}
			else {
				trigger_error("main already added $pId, $this->mModelName doesn't update");
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
	private function _getLocalObjectCollection($pObject, $pLocalObjectCollection) {
		return $this->_loadLocalObjectCollection($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return LocalObjectCollection
	 */
	private function _loadLocalObjectCollection($pObject) {
		trigger_error("+++++++++++ debut ++++++ $this->mModelName ++++++++");
		trigger_error(json_encode($pObject->toObject()));
		$lObjectCollectionCreator = new ObjectCollectionCreator();
		
		$plop = $lObjectCollectionCreator->execute($pObject);
		trigger_error($plop->toString());
		trigger_error("+++++++++++ fin ++++++++++++++");
		return $plop;
	}
	
}