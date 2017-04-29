<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\serialization\SqlTable;
use comhon\object\Object;
use comhon\object\collection\MainObjectCollection;
use comhon\visitor\ObjectCollectionCreator;
use comhon\serialization\SerializationUnit;
use comhon\exception\CastException;
use comhon\interfacer\Interfacer;

class MainModel extends Model {
	
	private $mSerialization            = null;
	private $mSerializationInitialised = false;
	
	protected final function _setSerialization() {
		if (!$this->mSerializationInitialised) {
			$this->mSerialization = ModelManager::getInstance()->getSerializationInstance($this);
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
		return !is_null($this->mSerialization) && ($this->mSerialization->getType() == $pSerializationType);
	}
	
	public function hasPartialSerialization() {
		return ($this->mSerialization instanceof SqlTable);
	}
	
	/**
	 * @return Object
	 */
	public function getSerializationSettings() {
		return is_null($this->mSerialization) ? null : $this->mSerialization->getSettings();
	}
	
	
	/** ***************************** generic ********************************* **/
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 */
	public function import($pInterfacedObject, Interfacer $pInterfacer) {
		$this->load();
		if ($pInterfacedObject instanceof \SimpleXMLElement) {
			$pInterfacedObject= dom_import_simplexml($pInterfacedObject);
		}
		$pInterfacer->verifyNode($pInterfacedObject);
		
		switch ($pInterfacer->getMergeType()) {
			case Interfacer::MERGE:
				$lObject = $this->_import($pInterfacedObject, $pInterfacer, null, true);
				break;
			case Interfacer::OVERWRITE:
				$lObject = $this->getOrCreateObjectInstanceFromInterfacedObject($pInterfacedObject, $pInterfacer, null, true);
				$lObject->reset();
				$this->_fillObject($lObject, $pInterfacedObject, $pInterfacer, new ObjectCollection(), true);
				break;
			case Interfacer::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromInterfacedObject($pInterfacedObject, $pInterfacer), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_import($pInterfacedObject, $pInterfacer, null, true);
				
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
	
	/**
	 *
	 * @param Object $pObject
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 */
	public function fillObject(Object $pObject, $pInterfacedObject, Interfacer $pInterfacer) {
		$this->load();
		if ($pInterfacedObject instanceof \SimpleXMLElement) {
			$pInterfacedObject= dom_import_simplexml($pInterfacedObject);
		}
		$pInterfacer->verifyNode($pInterfacedObject);
		
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromInterfacedObject($pInterfacedObject, $pInterfacer), $pInterfacer->hasToFlagValuesAsUpdated());
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObject($pObject, $pInterfacedObject, $pInterfacer, $this->_loadLocalObjectCollection($pObject), true);
		if ($pInterfacer->hasToFlagObjectAsLoaded()) {
			$pObject->setIsLoaded(true);
		}
	}
	
	/** ************************************************************************ **/
	
	
	public function fromSerializedStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, true, true, $pMergeType, $pTimeZone, false);
	}
	
	public function fromPublicStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, false, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromPrivateStdObject($pStdObject, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromStdObject($pStdObject, true, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromStdObject(\stdClass $pStdObject, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null, $pFlagAsUpdated = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, null);
				$lObject->reset();
				$this->_fillObjectFromStdObject($lObject, $pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromStdObject($pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				
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
		return $this->fromXml($pXml, true, true, $pMergeType, $pTimeZone, false);
	}
	
	public function fromPublicXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromXml($pXml, false, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromPrivateXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromXml($pXml, true, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromXml(\SimpleXMLElement $pXml, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null, $pFlagAsUpdated = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromXml($pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromXml($pXml, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, null);
				$lObject->reset();
				$this->_fillObjectFromXml($lObject, $pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromXml($pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				
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
		return $this->fromFlattenedArray($pRow, true, true, $pMergeType, $pTimeZone, false);
	}
	
	public function fromPublicFlattenedArray($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromFlattenedArray($pRow, false, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromPrivateFlattenedArray($pRow, $pMergeType = self::MERGE, $pTimeZone = null) {
		return $this->fromFlattenedArray($pRow, true, false, $pMergeType, $pTimeZone, true);
	}
	
	public function fromFlattenedArray(array $pRow, $pPrivate = false, $pUseSerializationName = false, $pMergeType = self::MERGE, $pTimeZone = null, $pFlagAsUpdated = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		switch ($pMergeType) {
			case self::MERGE:
				$lObject = $this->_fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $pFlagAsUpdated, null);
				$lObject->reset();
				$this->_fillObjectFromFlattenedArray($lObject, $pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, new ObjectCollection());
				break;
			case self::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_fromFlattenedArray($pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, null);
				
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
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, true, true, false, null, false, false);
				break;
			case self::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromFlattenedArray($pRow, true, true, false, null, false, false);
				$lObject->reset();
				$this->_fillObjectwithId($lObject, $this->getIdFromFlattenedArray($pRow, true, true), false);
				$lObject->setIsLoaded(false);
				break;
			case self::NO_MERGE:
				$lObject = $this->_buildObjectFromId($this->getIdFromFlattenedArray($pRow, true, true), false, false);
				break;
			default:
				throw new \Exception('undefined merge type '.$pMergeType);
		}
		return $lObject;
	}
	
	public function fillObjectFromSerializedStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, true, true, $pTimeZone, true, false);
	}
	
	public function fillObjectFromPublicStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, false, false, $pTimeZone, true, true);
	}
	
	public function fillObjectFromPrivateStdObject(Object $pObject, $pStdObject, $pTimeZone = null) {
		$this->fillObjectFromStdObject($pObject, $pStdObject, true, false, $pTimeZone, true, true);
	}
	
	public function fillObjectFromStdObject(Object $pObject, $pStdObject, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->load();
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromStdObject($pStdObject, $pPrivate, $pUseSerializationName), $pFlagAsUpdated);
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromStdObject($pObject, $pStdObject, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setIsLoaded(true);
		}
	}
	
	public function fillObjectFromSerializedXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, true, true, $pTimeZone, true, false);
	}
	
	public function fillObjectFromPublicXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, false, false, $pTimeZone, true, true);
	}
	
	public function fillObjectFromPrivateXml(Object $pObject, $pXml, $pTimeZone = null) {
		$this->fillObjectFromXml($pObject, $pXml, true, false, $pTimeZone, true, true);
	}
	
	public function fillObjectFromXml(Object $pObject, $pXml, $pPrivate, $pUseSerializationName, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromXml($pXml, $pPrivate, $pUseSerializationName), $pFlagAsUpdated);
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromXml($pObject, $pXml, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setIsLoaded(true);
		}
	}
	
	public function fillObjectfromSqlDatabase(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, true, true, $pTimeZone, true, false);
	}
	
	public function fillObjectfromPublicFlattenedArray(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, false, false, $pTimeZone, true, true);
	}
	
	public function fillObjectfromPrivateFlattenedArray(Object $pObject, $pRow, $pTimeZone = null) {
		$this->fillObjectFromFlattenedArray($pObject, $pRow, true, false, $pTimeZone, true, true);
	}
	
	public function fillObjectFromFlattenedArray(Object $pObject, $pRow, $pPrivate = false, $pUseSerializationName = false, $pTimeZone = null, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$this->load();
		$lDateTimeZone = new \DateTimeZone(is_null($pTimeZone) ? date_default_timezone_get() : $pTimeZone);
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromFlattenedArray($pRow, $pPrivate, $pUseSerializationName), $pFlagAsUpdated);
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObjectFromFlattenedArray($pObject, $pRow, $pPrivate, $pUseSerializationName, $lDateTimeZone, $pFlagAsUpdated, $this->_loadLocalObjectCollection($pObject));
		if ($pUpdateLoadStatus) {
			$pObject->setIsLoaded(true);
		}
	}
	
	private function _verifIdBeforeFillObject(Object $pObject, $pId, $pFlagAsUpdated) {
		if ($pObject->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		if (!$this->hasIdProperties()) {
			return ;
		}
		if (!$pObject->hasCompleteId()) {
			$this->_fillObjectwithId($pObject, $pId, $pFlagAsUpdated);
		}
		if (!$pObject->hasCompleteId()) {
			return ;
		}
		$lObjectId = $pObject->getId();
		if ($pId === 0) {
			if ($lObjectId !== 0 && $lObjectId !== '0') {
				$lMessageId = is_null($pId) ? 'null' : $pId;
				throw new \Exception("id must be the same as imported value id : {$pObject->getId()} !== $lMessageId");
			}
		} else if ($lObjectId === 0) {
			if ($pId !== 0 && $pId !== '0') {
				$lMessageId = is_null($pId) ? 'null' : $pId;
				throw new \Exception("id must be the same as imported value id : {$pObject->getId()} !== $lMessageId");
			}
		}
		else if ($pObject->getId() != $pId) {
			$lMessageId = is_null($pId) ? 'null' : $pId;
			throw new \Exception("id must be the same as imported value id : {$pObject->getId()} !== $lMessageId");
		}
		$lObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
		if (!is_null($lObject) && $lObject !== $pObject) {
		 	throw new \Exception("A different instance object with same id '$pId' already exists in MainObjectCollection.\n"
		 						.'If you want to build a new instance with this id, you must go through Model and specify merge type as '.Model::NO_MERGE.' (no merge)');
		}
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _exportId(Object $pObject, $pNodeName, Interfacer $pInterfacer) {
		$lNodeId = parent::_exportId($pObject, $pNodeName, $pInterfacer);
		
		if ($pInterfacer->hasToExportMainForeignObjects()) {
			if ($pObject->getModel() === $this) {
				$lModel = $this;
			} else {
				if (!$pObject->getModel()->isInheritedFrom($this)) {
					throw new \Exception('object doesn\'t have good model');
				}
				$lModel = $pObject->getModel();
			}
			$lValueId   = $this->_toInterfacedId($pObject, $pInterfacer);
			$lModelName = $lModel->getName();
			
			if (!$pInterfacer->hasMainForeignObject($lModelName, $lValueId)) {
				$pInterfacer->addMainForeignObject($pInterfacer->createNode('empty'), $lValueId, $pObject->getModel());
				$pInterfacer->addMainForeignObject($lModel->_export($pObject, $lModel->getName(), $pInterfacer, true), $lValueId, $pObject->getModel());
			}
		}
		return $lNodeId;
	}
	
	protected function _toStdObjectId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lId = parent::_toStdObjectId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
		
		if (is_array($pMainForeignObjects)) {
			$lValueId   = is_object($lId) ? $lId->id : $lId;
			$lModelName = $pObject->getModel()->getName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lValueId, $pMainForeignObjects[$lModelName]))) {
				$pMainForeignObjects[$lModelName][$lValueId] = null;
				$pMainForeignObjects[$lModelName][$lValueId] = $this->_toStdObject($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pOriginalUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				if ($pObject->getModel() !== $this) {
					unset($pMainForeignObjects[$lModelName][$lValueId]->{self::INHERITANCE_KEY});
				}
			}
		}
		return $lId;
	}
	
	protected function _toFlattenedValueId(Object $pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lId = parent::_toFlattenedValueId($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
	
		if (is_array($pMainForeignObjects)) {
			$lValueId   = is_array($lId) ? $lId['id'] : $lId;
			$lModelName = $pObject->getModel()->getName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lValueId, $pMainForeignObjects[$lModelName]))) {
				$pMainForeignObjects[$lModelName][$lValueId] = null;
				$pMainForeignObjects[$lModelName][$lValueId] = $this->_toFlattenedArray($pObject, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pOriginalUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
				if ($pObject->getModel() !== $this) {
					unset($pMainForeignObjects[$lModelName][$lValueId][self::INHERITANCE_KEY]);
				}
			}
		}
		return $lId;
	}
	
	protected function _toXmlId(Object $pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		$lId = parent::_toXmlId($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
		
		if (is_array($pMainForeignObjects)) {
			$lModelName = $pObject->getModel()->getName();
			if (!(array_key_exists($lModelName, $pMainForeignObjects) && array_key_exists($lId, $pMainForeignObjects[$lModelName]))) {
				$lXmlNode = new \SimpleXmlElement("<{$this->getName()}/>");
				$pMainForeignObjects[$lModelName][$lId] = null;
				$this->_toXml($pObject, $lXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pOriginalUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
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
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return Object|null null if load is unsuccessfull
	 */
	public function loadObject($pId, $pPropertiesFilter = null, $pForceLoad = false) {
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
			$lMainObject = $this->_buildObjectFromId($pId, false, false);
			$lNewObject = true;
		} else if ($lMainObject->isLoaded() && !$pForceLoad) {
			return $lMainObject;
		} else {
			$lNewObject = false;
		}
		
		try {
			return $this->loadAndFillObject($lMainObject, $pPropertiesFilter, $pForceLoad) ? $lMainObject : null;
		} catch (CastException $e) {
			// replace by finally block for php 5.5+
			if ($lNewObject) {
				$lMainObject->reset();
				throw $e;
			}
		}
	}
	
	/**
	 * load instancied object with serialized object
	 * @param Object $pObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return Object|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(Object $pObject, $pPropertiesFilter = null, $pForceLoad = false) {
		$lSuccess = false;
		$this->load();
		if (is_null($lSerializationUnit = $this->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		if (!$pObject->isLoaded() || $pForceLoad) {
			$lSuccess = $lSerializationUnit->loadObject($pObject, $pPropertiesFilter);
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
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection = null, $pIsloaded = true, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		$lModel = is_null($pInheritanceModelName) ? $this : $this->_getIneritedModel($pInheritanceModelName);
		
		if (!$lModel->hasIdProperties()) {
			$lMainObject = $lModel->getObjectInstance($pIsloaded);
		}
		else {
			$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $lModel->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $lModel->_buildObjectFromId($pId, $pIsloaded, $pFlagAsUpdated);
			}
			else {
				if (!MainObjectCollection::getInstance()->hasObject($pId, $lModel->mModelName, false)) {
					$lMainObject->cast($lModel);
				}
				if ($pUpdateLoadStatus) {
					$lMainObject->setIsLoaded(true);
				}
			}
		}
		return $lMainObject;
	}
	
	/**
	 * get or create an instance of Object
	 * @param integer|string $pId
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @param boolean $pIsForeign
	 * @return Object
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstanceGeneric($pId, Interfacer $pInterfacer, $pLocalObjectCollection, $pIsFirstLevel, $pIsForeign = false) {
		$lIsloaded = !$pIsForeign && (!$pIsFirstLevel || $pInterfacer->hasToFlagObjectAsLoaded());
		
		if (!$this->hasIdProperties()) {
			$lMainObject = $this->getObjectInstance($lIsloaded);
		}
		else {
			$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = $this->_buildObjectFromId($pId, $lIsloaded, $pInterfacer->hasToFlagValuesAsUpdated());
			}
			else {
				if (!MainObjectCollection::getInstance()->hasObject($pId, $this->mModelName, false)) {
					$lMainObject->cast($this);
				}
				if ($lIsloaded || ($pIsFirstLevel && $pInterfacer->getMergeType() !== Interfacer::MERGE)) {
					$lMainObject->setIsLoaded($lIsloaded);
				}
			}
		}
		return $lMainObject;
	}
	
	/**
	 * @param string $pInheritanceModelName
	 * @return Model;
	 */
	protected function _getIneritedModel($pInheritanceModelName) {
		if (ModelManager::getInstance()->hasModel($pInheritanceModelName)) {
			if (ModelManager::getInstance()->hasModel($pInheritanceModelName, $this->mModelName)) {
				throw new \Exception("cannot determine if model '$pInheritanceModelName' is local or main model");
			}
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName);
		} else {
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $this->mModelName);
		}
		if (!$lModel->isInheritedFrom($this)) {
			throw new \Exception("model '{$lModel->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $lModel;
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