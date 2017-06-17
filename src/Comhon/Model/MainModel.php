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
use Comhon\Object\ObjectUnique;

class MainModel extends Model {
	
	/** @var SerializationUnit */
	private $serialization = null;
	
	/** @var boolean */
	private $serializationInitialised = false;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_setSerialization()
	 */
	final protected function _setSerialization() {
		if (!$this->serializationInitialised) {
			$this->serialization = ModelManager::getInstance()->getSerializationInstance($this);
			$this->serializationInitialised = true;
		}
		if ($this->hasParent()) {
			if (count($this->getIdProperties()) != count($this->getParent()->getIdProperties())) {
				throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
			}
			foreach ($this->getParent()->getIdProperties() as $propertyName => $property) {
				if (!$this->hasIdProperty($propertyName) || !$property->isEqual($this->getIdProperty($propertyName))) {
					throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
				}
			}
		}
	}
	
	/**
	 * verify if serialization is loaded
	 * 
	 * @return boolean
	 */
	public function hasLoadedSerialization() {
		return $this->serializationInitialised;
	}
	
	/**
	 * get serialization
	 * 
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public function getSerialization() {
		return $this->serialization;
	}
	
	/**
	 * verify if model has serialization
	 * 
	 * @return boolean
	 */
	public function hasSerialization() {
		return !is_null($this->serialization);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::hasSqlTableUnit()
	 */
	public function hasSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getSqlTableUnit()
	 */
	public function getSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable) ? $this->serialization : null;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::hasSerializationUnit()
	 */
	public function hasSerializationUnit($serializationType) {
		return !is_null($this->serialization) && ($this->serialization->getType() == $serializationType);
	}
	
	/**
	 * get serialization settings if model has serialization
	 * 
	 * @return \Comhon\Object\ObjectUnique|null null if no serialization
	 */
	public function getSerializationSettings() {
		return is_null($this->serialization) ? null : $this->serialization->getSettings();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::import()
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		return $this->_importMain($interfacedObject, $interfacer, new ObjectCollection());
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_importMain()
	 */
	protected function _importMain($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection) {
		$this->load();
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject= dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new \Exception('interfaced object doesn\'t match with interfacer');
			}
		}
		
		switch ($interfacer->getMergeType()) {
			case Interfacer::MERGE:
				$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, $this, true);
				$this->_fillObject($object, $interfacedObject, $interfacer, $this->_loadLocalObjectCollection($object), $this, true);
				break;
			case Interfacer::OVERWRITE:
				$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, $this, true);
				$object->reset();
				$this->_fillObject($object, $interfacedObject, $interfacer, new ObjectCollection(), $this, true);
				break;
			case Interfacer::NO_MERGE:
				$existingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromInterfacedObject($interfacedObject, $interfacer), $this->modelName);
				if (!is_null($existingObject)) {
					MainObjectCollection::getInstance()->removeObject($existingObject);
				}
				$object = $this->_import($interfacedObject, $interfacer, new ObjectCollection(), $this, true);
				
				if (!is_null($existingObject)) {
					MainObjectCollection::getInstance()->removeObject($object);
					MainObjectCollection::getInstance()->addObject($existingObject);
				}
				break;
			default:
				throw new \Exception('undefined merge type '.$mergeType);
		}
		return $object;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::fillObject()
	 */
	public function fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer) {
		$this->load();
		$this->verifValue($object);
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject= dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new \Exception('interfaced object doesn\'t match with interfacer');
			}
		}
		
		$this->_verifIdBeforeFillObject($object, $this->getIdFromInterfacedObject($interfacedObject, $interfacer), $interfacer->hasToFlagValuesAsUpdated());
		
		MainObjectCollection::getInstance()->addObject($object, false);
		$this->_fillObject($object, $interfacedObject, $interfacer, $this->_loadLocalObjectCollection($object), $this, true);
		if ($interfacer->hasToFlagObjectAsLoaded()) {
			$object->setIsLoaded(true);
		}
	}
	
	/**
	 * verify comhon object to fill
	 * 
	 * check if has right model and right id
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 */
	private function _verifIdBeforeFillObject(ObjectUnique $object, $id, $flagAsUpdated) {
		if ($object->getModel() !== $this) {
			throw new \Exception('current model instance must be same instance of object model');
		}
		if (!$this->hasIdProperties()) {
			return ;
		}
		if (!$object->hasCompleteId()) {
			$this->_fillObjectwithId($object, $id, $flagAsUpdated);
		}
		if (!$object->hasCompleteId()) {
			return ;
		}
		$objectId = $object->getId();
		if ($id === 0) {
			if ($objectId !== 0 && $objectId !== '0') {
				$messageId = is_null($id) ? 'null' : $id;
				throw new \Exception("id must be the same as imported value id : {$object->getId()} !== $messageId");
			}
		} else if ($objectId === 0) {
			if ($id !== 0 && $id !== '0') {
				$messageId = is_null($id) ? 'null' : $id;
				throw new \Exception("id must be the same as imported value id : {$object->getId()} !== $messageId");
			}
		}
		else if ($object->getId() != $id) {
			$messageId = is_null($id) ? 'null' : $id;
			throw new \Exception("id must be the same as imported value id : {$object->getId()} !== $messageId");
		}
		$storedObject = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
		if (!is_null($storedObject) && $storedObject!== $object) {
		 	throw new \Exception("A different instance object with same id '$id' already exists in MainObjectCollection.\n"
		 						.'If you want to build a new instance with this id, you must go through Model and specify merge type as '.Interfacer::NO_MERGE.' (no merge)');
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_exportId()
	 * 
	 * export comhon object in foreign object list in interfacer (only if interfacer specify it)
	 */
	protected function _exportId(ComhonObject $object, $nodeName, Interfacer $interfacer) {
		$nodeId = parent::_exportId($object, $nodeName, $interfacer);
		
		if ($interfacer->hasToExportMainForeignObjects()) {
			if ($object->getModel() === $this) {
				$model = $this;
			} else {
				if (!$object->getModel()->isInheritedFrom($this)) {
					throw new \Exception('object doesn\'t have good model');
				}
				$model = $object->getModel();
			}
			$valueId   = $this->_toInterfacedId($object, $interfacer);
			$modelName = $model->getName();
			
			if (!$interfacer->hasMainForeignObject($modelName, $valueId)) {
				$interfacer->addMainForeignObject($interfacer->createNode('empty'), $valueId, $object->getModel());
				$interfacer->addMainForeignObject($model->_export($object, $model->getName(), $interfacer, true), $valueId, $object->getModel());
			}
		}
		return $nodeId;
	}
	
	/**
	 * load comhon object 
	 * 
	 * @param string|integer $id
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique|null null if load is unsuccessfull
	 */
	public function loadObject($id, $propertiesFilter = null, $forceLoad = false) {
		$this->load();
		if (!$this->hasIdProperties()) {
			throw new \Exception("model '$this->modelName' must have at least one id property to load object");
		}
		$mainObject = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
		
		if (is_null($mainObject)) {
			$mainObject = $this->_buildObjectFromId($id, false, false);
			$newObject = true;
		} else if ($mainObject->isLoaded() && !$forceLoad) {
			return $mainObject;
		} else {
			$newObject = false;
		}
		
		try {
			return $this->loadAndFillObject($mainObject, $propertiesFilter, $forceLoad) ? $mainObject : null;
		} catch (CastException $e) {
			// replace by finally block for php 5.5+
			if ($newObject) {
				$mainObject->reset();
				throw $e;
			}
		}
	}
	
	/**
	 * load instancied comhon object with serialized object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(ObjectUnique $object, $propertiesFilter = null, $forceLoad = false) {
		$success = false;
		$this->load();
		if (is_null($serializationUnit = $this->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		if (!$object->isLoaded() || $forceLoad) {
			$success = $serializationUnit->loadObject($object, $propertiesFilter);
		}
		return $success;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_getOrCreateObjectInstance()
	 */
	protected function _getOrCreateObjectInstance($id, Interfacer $interfacer, $localObjectCollection, $isFirstLevel, $isForeign = false) {
		$isloaded = !$isForeign && (!$isFirstLevel || $interfacer->hasToFlagObjectAsLoaded());
		
		if (!$this->hasIdProperties()) {
			$mainObject = $this->getObjectInstance($isloaded);
		}
		else {
			$mainObject = $localObjectCollection->getObject($id, $this->modelName);
			if (is_null($mainObject)) {
				$mainObject = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
			}
			if (is_null($mainObject)) {
				$mainObject = $this->_buildObjectFromId($id, $isloaded, $interfacer->hasToFlagValuesAsUpdated());
				$localObjectCollection->addObject($mainObject);
			}
			else {
				$localObjectCollection->addObject($mainObject, false);
				if (!$localObjectCollection->hasObject($id, $this->modelName, false)) {
					$mainObject->cast($this);
					$localObjectCollection->addObject($mainObject, false);
				}
				if ($isloaded || ($isFirstLevel && $interfacer->getMergeType() !== Interfacer::MERGE)) {
					$mainObject->setIsLoaded($isloaded);
				}
			}
		}
		return $mainObject;
	}
	
	/**
	 * get inherited model
	 * 
	 * @param string $inheritanceModelName
	 * @param MainModel $mainModelContainer
	 * @return Model;
	 */
	protected function _getIneritedModel($inheritanceModelName, MainModel $mainModelContainer) {
		if (ModelManager::getInstance()->hasModel($inheritanceModelName)) {
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
			if (ModelManager::getInstance()->hasModel($inheritanceModelName, $mainModelContainer->getName())) {
				throw new \Exception("cannot determine if model '$inheritanceModelName' is local or main model");
			}
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
		} else {
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName, $mainModelContainer->getName());
		}
		if (!$model->isInheritedFrom($this)) {
			throw new \Exception("model '{$model->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $model;
	}
	
	/**
	 * build object collection
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @return \Comhon\Object\Collection\ObjectCollection
	 */
	private function _loadLocalObjectCollection($object) {
		$objectCollectionCreator = new ObjectCollectionCreator();
		return $objectCollectionCreator->execute($object);
	}
	
}