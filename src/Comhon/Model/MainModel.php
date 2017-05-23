<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Serialization\SqlTable;
use Comhon\Object\ComhonObject;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Visitor\ObjectCollectionCreator;
use Comhon\Serialization\SerializationUnit;
use Comhon\Exception\CastException;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\StdObjectInterfacer;

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
	 * @return ComhonObject
	 */
	public function getSerializationSettings() {
		return is_null($this->mSerialization) ? null : $this->mSerialization->getSettings();
	}
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return ComhonObject
	 */
	public function import($pInterfacedObject, Interfacer $pInterfacer) {
		return $this->_importMain($pInterfacedObject, $pInterfacer, new ObjectCollection());
	}
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @throws \Exception
	 * @return ComhonObject
	 */
	protected function _importMain($pInterfacedObject, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection) {
		$this->load();
		if ($pInterfacedObject instanceof \SimpleXMLElement) {
			$pInterfacedObject= dom_import_simplexml($pInterfacedObject);
		}
		if (!$pInterfacer->isNodeValue($pInterfacedObject)) {
			if (($pInterfacer instanceof StdObjectInterfacer) && is_array($pInterfacedObject) && empty($pInterfacedObject)) {
				$pInterfacedObject = new \stdClass();
			} else {
				throw new \Exception('interfaced object doesn\'t match with interfacer');
			}
		}
		
		switch ($pInterfacer->getMergeType()) {
			case Interfacer::MERGE:
				$lObject = $this->_getOrCreateObjectInstanceFromInterfacedObject($pInterfacedObject, $pInterfacer, $pLocalObjectCollection, $this, true);
				$this->_fillObject($lObject, $pInterfacedObject, $pInterfacer, $this->_loadLocalObjectCollection($lObject), $this, true);
				break;
			case Interfacer::OVERWRITE:
				$lObject = $this->_getOrCreateObjectInstanceFromInterfacedObject($pInterfacedObject, $pInterfacer, $pLocalObjectCollection, $this, true);
				$lObject->reset();
				$this->_fillObject($lObject, $pInterfacedObject, $pInterfacer, new ObjectCollection(), $this, true);
				break;
			case Interfacer::NO_MERGE:
				$lExistingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromInterfacedObject($pInterfacedObject, $pInterfacer), $this->mModelName);
				if (!is_null($lExistingObject)) {
					MainObjectCollection::getInstance()->removeObject($lExistingObject);
				}
				$lObject = $this->_import($pInterfacedObject, $pInterfacer, new ObjectCollection(), $this, true);
				
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
	 * @param ComhonObject $pObject
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 */
	public function fillObject(ComhonObject $pObject, $pInterfacedObject, Interfacer $pInterfacer) {
		$this->load();
		if ($pInterfacedObject instanceof \SimpleXMLElement) {
			$pInterfacedObject= dom_import_simplexml($pInterfacedObject);
		}
		if (!$pInterfacer->isNodeValue($pInterfacedObject)) {
			if (($pInterfacer instanceof StdObjectInterfacer) && is_array($pInterfacedObject) && empty($pInterfacedObject)) {
				$pInterfacedObject = new \stdClass();
			} else {
				throw new \Exception('interfaced object doesn\'t match with interfacer');
			}
		}
		
		$this->_verifIdBeforeFillObject($pObject, $this->getIdFromInterfacedObject($pInterfacedObject, $pInterfacer), $pInterfacer->hasToFlagValuesAsUpdated());
		
		MainObjectCollection::getInstance()->addObject($pObject, false);
		$this->_fillObject($pObject, $pInterfacedObject, $pInterfacer, $this->_loadLocalObjectCollection($pObject), $this, true);
		if ($pInterfacer->hasToFlagObjectAsLoaded()) {
			$pObject->setIsLoaded(true);
		}
	}
	
	private function _verifIdBeforeFillObject(ComhonObject $pObject, $pId, $pFlagAsUpdated) {
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
		 						.'If you want to build a new instance with this id, you must go through Model and specify merge type as '.Interfacer::NO_MERGE.' (no merge)');
		}
	}
	
	/**
	 *
	 * @param ComhonObject $pObject
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _exportId(ComhonObject $pObject, $pNodeName, Interfacer $pInterfacer) {
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
	
	/**
	 * load serialized object 
	 * @param string|integer $pId
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return ComhonObject|null null if load is unsuccessfull
	 */
	public function loadObject($pId, $pPropertiesFilter = null, $pForceLoad = false) {
		$this->load();
		if (!$this->hasIdProperties()) {
			throw new \Exception("model '$this->mModelName' must have at least one id property to load object");
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
	 * @param ComhonObject $pObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return ComhonObject|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(ComhonObject $pObject, $pPropertiesFilter = null, $pForceLoad = false) {
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
	 * get or create an instance of ComhonObject
	 * @param integer|string $pId
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @param boolean $pIsForeign
	 * @return ComhonObject
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstance($pId, Interfacer $pInterfacer, $pLocalObjectCollection, $pIsFirstLevel, $pIsForeign = false) {
		$lIsloaded = !$pIsForeign && (!$pIsFirstLevel || $pInterfacer->hasToFlagObjectAsLoaded());
		
		if (!$this->hasIdProperties()) {
			$lMainObject = $this->getObjectInstance($lIsloaded);
		}
		else {
			$lMainObject = $pLocalObjectCollection->getObject($pId, $this->mModelName);
			if (is_null($lMainObject)) {
				$lMainObject = MainObjectCollection::getInstance()->getObject($pId, $this->mModelName);
			}
			if (is_null($lMainObject)) {
				$lMainObject = $this->_buildObjectFromId($pId, $lIsloaded, $pInterfacer->hasToFlagValuesAsUpdated());
				$pLocalObjectCollection->addObject($lMainObject);
			}
			else {
				$pLocalObjectCollection->addObject($lMainObject, false);
				if (!$pLocalObjectCollection->hasObject($pId, $this->mModelName, false)) {
					$lMainObject->cast($this);
					$pLocalObjectCollection->addObject($lMainObject, false);
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
	 * @param MainModel $pParentMainModel
	 * @return Model;
	 */
	protected function _getIneritedModel($pInheritanceModelName, MainModel $pParentMainModel) {
		if (ModelManager::getInstance()->hasModel($pInheritanceModelName)) {
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName);
			if (ModelManager::getInstance()->hasModel($pInheritanceModelName, $pParentMainModel->getName())) {
				throw new \Exception("cannot determine if model '$pInheritanceModelName' is local or main model");
			}
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName);
		} else {
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $pParentMainModel->getName());
		}
		if (!$lModel->isInheritedFrom($this)) {
			throw new \Exception("model '{$lModel->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $lModel;
	}
	
	/**
	 * 
	 * @param ComhonObject $pObject
	 * @return ObjectCollection
	 */
	private function _loadLocalObjectCollection($pObject) {
		$lObjectCollectionCreator = new ObjectCollectionCreator();
		return $lObjectCollectionCreator->execute($pObject);
	}
	
}