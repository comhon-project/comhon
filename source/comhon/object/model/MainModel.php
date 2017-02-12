<?php
namespace comhon\object\model;

use comhon\object\singleton\ModelManager;
use comhon\object\object\serialization\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\MainObjectCollection;
use comhon\exception\PropertyException;
use comhon\visitor\ObjectCollectionCreator;
use \stdClass;
use comhon\object\object\serialization\SerializationUnit;

class MainModel extends Model {
	
	private $mSerialization            = null;
	private $mSerializationInitialised = false;
	
	protected final function _setSerialization() {
		if (!$this->mSerializationInitialised) {
			$this->mSerialization = ModelManager::getInstance()->getSerialization($this);
			$this->mSerializationInitialised = true;
		}
		if ($this->hasExtendsModel()) {
			if (count($this->getIdProperties()) != count($this->getExtendsModel()->getIdProperties())) {
				throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
			}
			foreach ($this->getExtendsModel()->getIdProperties() as $lPropertyName => $lProperty) {
				if (!$this->hasIdProperty($lPropertyName) || !$lProperty->isEqual($this->getIdProperty($lPropertyName))) {
					throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
				}
			}
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
	
	public function fromSerializedStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, true, true, $pMergeType, $pTimeZone);
	}
	
	public function fromPublicStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, false, false, $pMergeType, $pTimeZone);
	}
	
	public function fromPrivateStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, true, false, $pMergeType, $pTimeZone);
	}
	
	public function fromStdObject(\stdClass $pStdObject, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromObject($pStdObject, $pPrivate, $pUseSerializationName, null);
				$lObject->resetValues();
				$this->_fillObjectFromStdObject($lObject, $pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lObject);
					MainObjectCollection::getInstance()->addObject($lExistingObject);
				}
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromSerializedXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromXml($pXml, true, true, $pMergeType, $pTimeZone);
	}
	
	public function fromPublicXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromXml($pXml, false, false, $pMergeType, $pTimeZone);
	}
	
	public function fromPrivateXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromXml($pXml, true, false, $pMergeType, $pTimeZone);
	}
	
	public function fromXml(\SimpleXMLElement $pXml, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromXml($pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, null);
				$lObject->resetValues();
				$this->_fillObjectFromXml($lObject, $pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromXml($pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lObject);
					MainObjectCollection::getInstance()->addObject($lExistingObject);
				}
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromSqlDatabase($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromFlattenedArray($pRow, true, true, $pMergeType, $pTimeZone);
	}
	
	public function fromPublicFlattenedArray($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromFlattenedArray($pRow, false, false, $pMergeType, $pTimeZone);
	}
	
	public function fromPrivateFlattenedArray($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromFlattenedArray($pRow, true, false, $pMergeType, $pTimeZone);
	}
	
	public function fromFlattenedArray(array $pRow, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, null);
				$lObject->resetValues();
				$this->_fillObjectFromFlattenedArray($lObject, $pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, null);
				
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lObject);
					MainObjectCollection::getInstance()->addObject($lExistingObject);
				}
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fromSqlDatabaseId(array $pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		$this->load();
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, true, true, null, false, false);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, true, true, null, false, false);
				$lObject->resetValues();
				$this->_fillObjectwithId($lObject, $this->getIdFromFlattenedArray($pRow, true, true));
				$lObject->setUnLoadStatus();
				break;
			case self::NO_MERGE:
				$lObject = $this->_buildObjectFromId($this->getIdFromFlattenedArray($pRow, true, true), false);
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fillObjectFromSerializedStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, true, true, $pTimeZone);
	}
	
	public function fillObjectFromPublicStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, false, false, $pTimeZone);
	}
	
	public function fillObjectFromPrivateStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, true, false, $pTimeZone);
	}
	
	public function fillObjectFromStdObject(Object $pObject, $pStdObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->load();
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName));
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromStdObject($pObject, $pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectFromSerializedXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, true, true, $pTimeZone);
	}
	
	public function fillObjectFromPublicXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, false, false, $pTimeZone);
	}
	
	public function fillObjectFromPrivateXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, true, false, $pTimeZone);
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName));
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromXml($pObject, $pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	public function fillObjectfromSqlDatabase(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, true, true, $pTimeZone);
	}
	
	public function fillObjectfromPublicFlattenedArray(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, false, false, $pTimeZone);
	}
	
	public function fillObjectfromPrivateFlattenedArray(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, true, false, $pTimeZone);
	}
	
	public function fillObjectFromFlattenedArray(Object $pObject, $pRow, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName));
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromFlattenedArray($pObject, $pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setLoadStatus();
		}
	}
	
	private function _verifIdBeforeFillObject(Object $pObject, $pId) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		if (!$this->hasIdProperties()) {
			return ;
		}
		if (!$pObject->hasCompleteId()) {
			$this->_fillObjectwithId($pObject, $pId);
		}
		if (!$pObject->hasCompleteId()) {
			return ;
		}
		$lObjectId = $pObject->getId();
		if ($pId === 0) {
			if ($lObjectId !== 0 && $lObjectId !== '0') {
				throw new \Exception("id must be the same as stdObject : {$pObject->getId()} !== {$this->getIdFromStdObject($pStdObject, true, false)}");
			}
		} else if ($lObjectId === 0) {
			if ($pId !== 0 && $pId !== '0') {
				throw new \Exception("id must be the same as stdObject : {$pObject->getId()} !== {$this->getIdFromStdObject($pStdObject, true, false)}");
			}
		}
		else if ($pObject->getId() != $pId) {
			throw new \Exception("id must be the same as stdObject : {$pObject->getId()} !== {$this->getIdFromStdObject($pStdObject, true, false)}");
		}
		$lObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
		if (!is_null($lObject) && $lObject !== $pObject) {
		 	throw new \Exception("A different instance object with same id '$pId' already exists in MainObjectCollection.\n"
		 						.'If you want to build a new instance with this id, you must go through Model and specify merge type as '.Model::NO_MERGE.' (no merge)');
		}
	}
	
	protected function _toStdObjectId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lId = parent::_toStdObjectId($pObject, $pPrivate, $pUseSerializationName, $pMainForeignObjects);
		
		if (is_array($pMainForeignObjects)) {
			$lValueId   = is_object($lId) ? $lId->id : $lId;
			$lModelName = $pObject->getModel()->getModelName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lValueId, $pMainForeignObjects[$lModelName]))) {
				$pMainForeignObjects[$lModelName][$lValueId] = null;
				$pMainForeignObjects[$lModelName][$lValueId] = $this->_toStdObject($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				if ($pObject->getModel() !== $this) {
					unset($pMainForeignObjects[$lModelName][$lValueId]->{self::INHERITANCE_KEY});
				}
			}
		}
		return $lId;
	}
	
	protected function _toFlattenedValueId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lId = parent::_toFlattenedValueId($pObject, $pPrivate, $pUseSerializationName, $pMainForeignObjects);
	
		if (is_array($pMainForeignObjects)) {
			$lValueId   = is_array($lId) ? $lId['id'] : $lId;
			$lModelName = $pObject->getModel()->getModelName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lValueId, $pMainForeignObjects[$lModelName]))) {
				$pMainForeignObjects[$lModelName][$lValueId] = null;
				$pMainForeignObjects[$lModelName][$lValueId] = $this->_toFlattenedArray($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				if ($pObject->getModel() !== $this) {
					unset($pMainForeignObjects[$lModelName][$lValueId][self::INHERITANCE_KEY]);
				}
			}
		}
		return $lId;
	}
	
	protected function _toXmlId(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lId = parent::_toXmlId($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pMainForeignObjects);
		
		if (is_array($pMainForeignObjects)) {
			$lModelName = $pObject->getModel()->getModelName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$lModelName]))) {
				$lXmlNode = new \SimpleXmlElement("<{$this->getModelName()}/>");
				$pMainForeignObjects[$lModelName][$lId] = null;
				$this->_toXml($pObject, $lXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				$pMainForeignObjects[$lModelName][$lId] = $lXmlNode;
				if ($pObject->getModel() !== $this) {
					unset($pMainForeignObjects[$lModelName][$lId][self::INHERITANCE_KEY]);
				}
			}
		}
		return $lId;
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
		if (!$this->hasIdProperties()) {
			throw new \Exception("model '$this->mModelName' must have at least one id property to load object");
		}
		if (!$this->hasUniqueIdProperty()) {
			// we decode and encode id to be sure to have good type on each id-values,
			$pId = $this->encodeId($this->decodeId($pId));
		}
		$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
		
		if (is_null($lMainObject)) {
			$lMainObject = $this->_buildObjectFromId($pId, false);
		} else if ($lMainObject->isLoaded() && !$pForceLoad) {
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
			throw new \Exception('model doesn\'t have serialization');
		}
		if (!$pObject->isLoaded() || $pForceLoad) {
			$lSuccess = $lSerializationUnit->loadObject($pObject);
		}
		return $lSuccess;
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string $pInheritanceModelName
	 * @param ObjectCollection $pLocalObjectCollection not used but we need to have it to match with LocalModel
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status
	 * @return Object
	 */
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection = null, $pIsloaded = true, $pUpdateLoadStatus = true) {
		if (is_null($pInheritanceModelName)) {
			$lModel = $this;
		} else {
			if (ModelManager::getInstance()->hasModel($pInheritanceModelName)) {
				if (ModelManager::getInstance()->hasModel($pInheritanceModelName, $this->mModelName)) {
					throw new \Exception("cannot determine if model '$pInheritanceModelName' is local or main model");
				}
				$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName);
			} else {
				$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $this->mModelName);
			}
			if (!$lModel->isInheritedFrom($this)) {
				throw new \Exception("model '{$lModel->getModelName()}' doesn't inherit from '{$this->getModelName()}'");
			}
		}
		
		if (!$lModel->hasIdProperties()) {
			$lMainObject = $lModel->getObjectInstance($pIsloaded);
			MainObjectCollection::getInstance()->addObject($lMainObject);
			//trigger_error("new main without id $pId, $lModel->mModelName");
		}
		else {
			$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $lModel->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $lModel->_buildObjectFromId($pId, $pIsloaded);
				MainObjectCollection::getInstance()->addObject($lMainObject);
				//trigger_error("new main $pId, $lModel->mModelName");
			}
			else {
				if (!MainObjectCollection::getInstance()->hasObject($pId, $lModel->mModelName, false)) {
					$lMainObject->cast($lModel);
				}
				if ($pUpdateLoadStatus) {
					//trigger_error("main already added $pId, $lModel->mModelName");
					//trigger_error("update main status ".var_export($lMainObject->isLoaded(), true));
					$lMainObject->setLoadStatus();
				} else {
					//trigger_error("main already added $pId, $lModel->mModelName doesn't update");
				}
			}
		}
		return $lMainObject;
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param ObjectCollection $pLocalObjectCollection
	 * @return ObjectCollection
	 */
	protected function _getLocalObjectCollection($pObject, $pLocalObjectCollection) {
		return $this->_loadLocalObjectCollection($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return ObjectCollection
	 */
	private function _loadLocalObjectCollection($pObject) {
		$lObjectCollectionCreator = new ObjectCollectionCreator();
		return $lObjectCollectionCreator->execute($pObject);
	}
	
}