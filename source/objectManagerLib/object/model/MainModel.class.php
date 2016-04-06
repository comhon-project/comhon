<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\ObjectCollection;
use objectManagerLib\exception\PropertyException;
use \stdClass;

class MainModel extends Model {
	
	private $mSerialization            = null;
	private $mSerializationInitialised = false;
	private $mHasSerializationReturn   = false;
	
	protected final function _setSerialization() {
		if (!$this->mSerializationInitialised) {
			$this->mSerialization = InstanceModel::getInstance()->getSerialization($this);
			if (!is_null($this->mSerialization)) {
				$this->mHasSerializationReturn = $this->mSerialization->hasReturnValue();
			}
			$this->mSerializationInitialised = true;
		}
	}
	
	public function hasLoadedSerialization() {
		return $this->mSerializationInitialised;
	}
	
	public function hasSerializationReturn() {
		return $this->mHasSerializationReturn;
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
	
	public function hasSqlTableUnitComposition($pParentModel) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			return false;
		}
		return $lSqlTableUnit->isComposition($pParentModel, $this->getSerializationName());
	}
	
	public function fromObject($pPhpObject) {
		$this->load();
		return $this->_fromObject($pPhpObject, null /** no need to pass id */);
	}
	
	public function fromXml($pXml) {
		$this->load();
		return $this->_fromXml($pXml, null /** no need to pass id */);
	}
	
	public function fromSqlDataBase($pRow, $pAddUnloadValues = true) {
		$this->load();
		return $this->_fromSqlDataBase($pRow, $pAddUnloadValues, null /** no need to pass id */);
	}
	
	public function fillObjectFromPhpObject($pObject, $pPhpObject) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromPhpObject($pObject, $pPhpObject, $this->getIdFromPhpObject($pPhpObject));
	}
	
	public function fillObjectFromXml($pObject, $pXml) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromXml($pObject, $pXml, $this->getIdFromXml($pXml));
	}
	
	public function fillObjectFromSqlDatabase($pObject, $pRow, $pAddUnloadValues = true) {
		$this->load();
		ObjectCollection::getInstance()->addMainObject($pObject, false);
		$this->_fillObjectFromSqlDatabase($pObject, $pRow, $pAddUnloadValues);
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
	 * @param boolean $pForceLoad if object already exists, force to reload serialized object
	 * @throws \Exception
	 * @return Object
	 */
	public function loadObject($pId, $pForceLoad = false) {
		$this->load();
		if (is_null($lSerializationUnit = $this->getSerialization())) {
			throw new \Exception("model doesn't have serialization");
		}
		if (count($lIdProperties = $this->getIdProperties()) != 1) {
			throw new \Exception("model must have one and only one id property");
		}
		$lMainObject = ObjectCollection::getInstance()->getMainObject($pId, $this->mModelName);
		
		if (is_null($lMainObject)) {
			$lMainObject = $this->getObjectInstance();
			$lMainObject->setValue($lIdProperties[0], $pId);
			ObjectCollection::getInstance()->addMainObject($lMainObject);
			$lSuccess = $lSerializationUnit->loadObject($lMainObject, $pId);
		}
		else if ($pForceLoad) {
			$lSuccess = $lSerializationUnit->loadObject($lMainObject, $pId);
		}
		return $lMainObject;
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string|integer $pMainObjectId not used but we need to have it to match with LocalModel
	 * @param boolean $pIsloaded
	 * @return array [Object,string] second element is the key in ObjectCollection where we can found Object returned
	 */
	protected function _getOrCreateObjectInstance($pId, $pMainObjectId = null, $pIsloaded = true) {
		if (!$this->hasIdProperty()) {
			$lMainObject = $this->getObjectInstance($pIsloaded);
			$lObjectCollectionKey = ObjectCollection::getInstance()->addMainObject($lMainObject);
			trigger_error("new main without id $pId, $this->mModelName");
		}
		else {
			if (count($lIdProperties = $this->getIdProperties()) != 1) {
				throw new \Exception("model must have one and only one id property");
			}
			$lMainObject = ObjectCollection::getInstance()->getMainObject($pId, $this->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $this->getObjectInstance($pIsloaded);
				if (!is_null($pId)) {
					$lMainObject->setValue($lIdProperties[0], $pId);
				}
				$lObjectCollectionKey = ObjectCollection::getInstance()->addMainObject($lMainObject);
				trigger_error("new main $pId, $this->mModelName");
			}
			else {
				trigger_error("main already added $pId, $this->mModelName");
				$lObjectCollectionKey = $pId;
			}
		}
		return array($lMainObject, $lObjectCollectionKey);
	}
	
}