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
	
	private $serialization            = null;
	private $serializationInitialised = false;
	
	protected final function _setSerialization() {
		if (!$this->serializationInitialised) {
			$this->serialization = ModelManager::getInstance()->getSerializationInstance($this);
			$this->serializationInitialised = true;
		}
		if ($this->hasExtendsModel()) {
			if (count($this->getIdProperties()) != count($this->getExtendsModel()->getIdProperties())) {
				throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
			}
			foreach ($this->getExtendsModel()->getIdProperties() as $propertyName => $property) {
				if (!$this->hasIdProperty($propertyName) || !$property->isEqual($this->getIdProperty($propertyName))) {
					throw new \Exception('extended model with same serialization doesn\'t have same id(s)');
				}
			}
		}
	}
	
	public function hasLoadedSerialization() {
		return $this->serializationInitialised;
	}
	
	/**
	 * @return SerializationUnit
	 */
	public function getSerialization() {
		return $this->serialization;
	}
	
	public function hasSerialization() {
		return !is_null($this->serialization);
	}
	
	public function hasSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable);
	}
	
	public function getSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable) ? $this->serialization : null;
	}
	
	public function hasSerializationUnit($serializationType) {
		return !is_null($this->serialization) && ($this->serialization->getType() == $serializationType);
	}
	
	public function hasPartialSerialization() {
		return ($this->serialization instanceof SqlTable);
	}
	
	/**
	 * @return ComhonObject
	 */
	public function getSerializationSettings() {
		return is_null($this->serialization) ? null : $this->serialization->getSettings();
	}
	
	/**
	 *
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return ComhonObject
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		return $this->_importMain($interfacedObject, $interfacer, new ObjectCollection());
	}
	
	/**
	 *
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @throws \Exception
	 * @return ComhonObject
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
	 * @param ComhonObject $object
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 */
	public function fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer) {
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
		
		$this->_verifIdBeforeFillObject($object, $this->getIdFromInterfacedObject($interfacedObject, $interfacer), $interfacer->hasToFlagValuesAsUpdated());
		
		MainObjectCollection::getInstance()->addObject($object, false);
		$this->_fillObject($object, $interfacedObject, $interfacer, $this->_loadLocalObjectCollection($object), $this, true);
		if ($interfacer->hasToFlagObjectAsLoaded()) {
			$object->setIsLoaded(true);
		}
	}
	
	private function _verifIdBeforeFillObject(ComhonObject $object, $id, $flagAsUpdated) {
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
	 * @param ComhonObject $object
	 * @param string $nodeName
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
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
	 * load serialized object 
	 * @param string|integer $id
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return ComhonObject|null null if load is unsuccessfull
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
	 * load instancied object with serialized object
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return ComhonObject|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(ComhonObject $object, $propertiesFilter = null, $forceLoad = false) {
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
	 * get or create an instance of ComhonObject
	 * @param integer|string $id
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @param boolean $isForeign
	 * @return ComhonObject
	 * @throws \Exception
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
	 * @param string $inheritanceModelName
	 * @param MainModel $parentMainModel
	 * @return Model;
	 */
	protected function _getIneritedModel($inheritanceModelName, MainModel $parentMainModel) {
		if (ModelManager::getInstance()->hasModel($inheritanceModelName)) {
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
			if (ModelManager::getInstance()->hasModel($inheritanceModelName, $parentMainModel->getName())) {
				throw new \Exception("cannot determine if model '$inheritanceModelName' is local or main model");
			}
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
		} else {
			$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName, $parentMainModel->getName());
		}
		if (!$model->isInheritedFrom($this)) {
			throw new \Exception("model '{$model->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $model;
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @return ObjectCollection
	 */
	private function _loadLocalObjectCollection($object) {
		$objectCollectionCreator = new ObjectCollectionCreator();
		return $objectCollectionCreator->execute($object);
	}
	
}