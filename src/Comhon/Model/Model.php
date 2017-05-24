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
use Comhon\Object\Object;
use Comhon\Object\ObjectArray;
use Comhon\Exception\PropertyException;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;

abstract class Model {

	/**
	 * array used to avoid infinite loop when objects are visited
	 * @var integer[]
	 */
	private static $instanceObjectHash = [];
	
	/** @var string */
	protected $modelName;
	
	/** @var boolean */
	protected $isLoaded = false;
	
	/** @var boolean */
	protected $isLoading = false;
	
	/** @var Model */
	private $extendsModel;
	
	/** @var string */
	private $objectClass = 'Comhon\Object\Object';
	
	/** @var boolean */
	private $isExtended = false;
	
	/** @var Property[] */
	private $properties   = [];
	
	/** @var Property[] */
	private $idProperties = [];
	
	/** @var Property[] */
	private $aggregations = [];
	
	/** @var Property[] */
	private $publicProperties  = [];
	
	/** @var Property[] */
	private $serializableProperties = [];
	
	/** @var Property[] */
	private $propertiesWithDefaultValues = [];
	
	/** @var Property[] */
	private $multipleForeignProperties = [];
	
	/** @var Property[] */
	private $complexProperties = [];
	
	/** @var Property[] */
	private $dateTimeProperties = [];
	
	/** @var Property */
	private $uniqueIdProperty;
	
	/** @var boolean */
	private $hasPrivateIdProperty;
	
	/**
	 * don't instanciate a model by yourself because it take time.
	 * to get a model instance use singleton ModelManager.
	 */
	public function __construct($modelName, $loadModel) {
		$this->modelName = $modelName;
		if ($loadModel) {
			$this->load();
		}
	}
	
	public final function load() {
		if (!$this->isLoaded && !$this->isLoading) {
			$this->isLoading = true;
			$result = ModelManager::getInstance()->getProperties($this);
			$this->extendsModel = $result[ModelManager::EXTENDS_MODEL];
			$this->_setProperties($result[ModelManager::PROPERTIES]);

			if (!is_null($result[ModelManager::OBJECT_CLASS])) {
				if ($this->objectClass !== $result[ModelManager::OBJECT_CLASS]) {
					$this->objectClass = $result[ModelManager::OBJECT_CLASS];
					$this->isExtended = true;
				}
			}
			$this->_setSerialization();
			$this->_init();
			$this->isLoaded  = true;
			$this->isLoading = false;
		}
	}
	
	protected function _setSerialization() {}
	
	protected function _init() {
		// you can overide this function in inherited class to initialize others attributes
	}
	
	public function getObjectClass() {
		return $this->objectClass;
	}
	
	/**
	 * 
	 * @param boolean $isloaded
	 * @return ComhonObject
	 */
	public function getObjectInstance($isloaded = true) {
		if ($this->isExtended) {
			$object = new $this->objectClass($isloaded);

			if ($object->getModel() !== $this) {
				throw new \Exception("object doesn't have good model. {$this->getName()} expected, {$object->getModel()->getName()} given");
			}
			return $object;
		} else {
			return new Object($this, $isloaded);
		}
		
	}
	
	public function getExtendsModel() {
		return $this->extendsModel;
	}
	
	public function hasExtendsModel() {
		return !is_null($this->extendsModel);
	}
	
	public function isInheritedFrom(Model $model) {
		$currentModel = $this;
		$isInherited = false;
		while (!is_null($currentModel->extendsModel) && !$isInherited) {
			$isInherited = $model === $currentModel->extendsModel;
			$currentModel = $currentModel->extendsModel;
		}
		return $isInherited;
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
		throw new \Exception('can\'t apply function. Only callable for MainModel or LocalModel');
	}
	
	public function getName() {
		return $this->modelName;
	}
	
	public function getMainModelName() {
		return $this->modelName;
	}
	
	/**
	 * 
	 * @return Property[]
	 */
	public function getProperties() {
		return $this->properties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getSpecificProperties($private, $serialization) {
		return $private ? $this->properties : $this->publicProperties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getComplexProperties() {
		return $this->complexProperties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getDateTimeProperties() {
		return $this->dateTimeProperties;
	}
	
	/**
	 *
	 * @return Property[]
	 */
	public function getPublicProperties() {
		return $this->publicProperties;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getPropertiesNames() {
		return array_keys($this->properties);
	}
	
	/**
	 * 
	 * @param string $propertyName
	 * @param string $throwException
	 * @throws PropertyException
	 * @return Property
	 */
	public function getProperty($propertyName, $throwException = false) {
		if ($this->hasProperty($propertyName)) {
			return $this->properties[$propertyName];
		}
		else if ($throwException) {
			throw new PropertyException($this, $propertyName);
		}
		return null;
	}
	
	/**
	 *
	 * @param string $propertyName
	 * @param string $throwException
	 * @throws PropertyException
	 * @return Property
	 */
	public function getIdProperty($propertyName, $throwException = false) {
		if ($this->hasIdProperty($propertyName)) {
			return $this->idProperties[$propertyName];
		}
		else if ($throwException) {
			throw new PropertyException($this, $propertyName);
		}
		return null;
	}
	
	/**
	 * 
	 * @param Property[] $properties
	 */
	protected function _setProperties($properties) {
		$publicIdProperties = [];
		
		// first we register id properties to be sure to have them in first positions
		foreach ($properties as $property) {
			if ($property->isId()) {
				$this->idProperties[$property->getName()] = $property;
				if (!$property->isPrivate()) {
					$publicIdProperties[$property->getName()] = $property;
				}
				if ($property->isSerializable()) {
					$this->serializableProperties[$property->getName()] = $property;
					if (!$property->isPrivate()) {
						$this->publicSerializableProperties[$property->getName()] = $property;
					}
				}
				if (!$property->isPrivate()) {
					$this->publicProperties[$property->getName()] = $property;
				}
				$this->properties[$property->getName()] = $property;
			}
		}
		// second we register others properties
		foreach ($properties as $property) {
			if (!$property->isId()) {
				if ($property->hasDefaultValue()) {
					$this->propertiesWithDefaultValues[$property->getName()] = $property;
				} else if ($property->isAggregation()) {
					$this->aggregations[$property->getName()] = $property;
				} else if ($property->hasMultipleSerializationNames()) {
					$this->multipleForeignProperties[$property->getName()] = $property;
				}
				if ($property->isSerializable()) {
					$this->serializableProperties[$property->getName()] = $property;
					if (!$property->isPrivate()) {
						$this->publicSerializableProperties[$property->getName()] = $property;
					}
				}
				if (!$property->isPrivate()) {
					$this->publicProperties[$property->getName()] = $property;
				}
				if ($property->isComplex()) {
					$this->complexProperties[$property->getName()] = $property;
				}
				if ($property->hasModelDateTime()) {
					$this->dateTimeProperties[$property->getName()] = $property;
				}
				$this->properties[$property->getName()] = $property;
			}
		}
		if (count($this->idProperties) == 1) {
			reset($this->idProperties);
			$this->uniqueIdProperty = current($this->idProperties);
		}
		if (count($this->idProperties) != count($publicIdProperties)) {
			$this->hasPrivateIdProperty = true;
		}
	}
	
	public function hasProperty($propertyName) {
		return array_key_exists($propertyName, $this->properties);
	}
	
	public function hasIdProperty($propertyName) {
		return array_key_exists($propertyName, $this->idProperties);
	}
	
	/**
	 * get foreign properties that have their own serialization
	 * @param string $serializationType ("sqlTable", "jsonFile"...)
	 * @return Property[]
	 */
	public function getForeignSerializableProperties($serializationType) {
		$properties = [];
		foreach ($this->properties as $propertyName => $property) {
			if (($property instanceof ForeignProperty) && $property->hasSerializationUnit($serializationType)) {
				$properties[] = $property;
			}
		}
		return $properties;
	}
	
	public function getSerializableProperties() {
		return $this->serializableProperties;
	}
	
	public function getIdProperties() {
		return $this->idProperties;
	}
	
	/**
	 * get id property if there is one and only one id property
	 * @return Property|null
	 */
	public function getUniqueIdProperty() {
		return $this->uniqueIdProperty;
	}
	
	public function hasUniqueIdProperty() {
		return !is_null($this->uniqueIdProperty);
	}
	
	public function hasPrivateIdProperty() {
		return $this->hasPrivateIdProperty;
	}
	
	public function hasIdProperties() {
		return !empty($this->idProperties);
	}
	
	/**
	 * 
	 * @return Property:
	 */
	public function getPropertiesWithDefaultValues() {
		return $this->propertiesWithDefaultValues;
	}
	
	/**
	 * 
	 * @return AggregationProperty[]:
	 */
	public function getAggregations() {
		return $this->aggregations;
	}
	
	public function getSerializationIds() {
		$serializationIds = [];
		foreach ($this->idProperties as $idProperty) {
			$serializationIds[] = $idProperty->getSerializationName();
		}
		return $serializationIds;
	}
	
	public function getFirstIdProperty() {
		reset($this->idProperties);
		return empty($this->idProperties) ? null : current($this->idProperties);
	}
	
	public function isLoaded() {
		return $this->isLoaded;
	}
	
	public function isComplex() {
		return true;
	}
	
	/**
	 * @return null
	 */
	public function getSerialization() {
		return null;
	}
	
	public function hasSerializationUnit($serializationType) {
		return false;
	}
	
	public function hasPartialSerialization() {
		return false;
	}
	
	/**
	 * @return null
	 */
	public function getSerializationSettings() {
		return null;
	}
	
	public function hasSqlTableUnit() {
		return false;
	}
	
	public function getSqlTableUnit() {
		return nul;
	}
	
	/**
	 * @param array $idValues encode id in json format
	 */
	public function encodeId($idValues) {
		return empty($idValues) ? null : json_encode($idValues);
	}
	
	/**
	 * @param string $id decode id from json format
	 */
	public function decodeId($id) {
		return json_decode($id);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 */
	protected function _addMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if ($interfacer->hasToExportMainForeignObjects() && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->addMainForeignObject($interfacer->createNode('empty'), $object->getId(), $object->getModel());
		}
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 */
	protected function _removeMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if ($interfacer->hasToExportMainForeignObjects() && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->removeMainForeignObject($object->getId(), $object->getModel());
		}
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 * @return mixed|null
	 */
	public final function export(ComhonObject $object, Interfacer $interfacer) {
		$interfacer->initializeExport();
		self::$instanceObjectHash = [];
		$this->_addMainCurrentObject($object, $interfacer);
		$node = $this->_export($object, $this->getName(), $interfacer, true);
		$this->_removeMainCurrentObject($object, $interfacer);
		self::$instanceObjectHash = [];
		$interfacer->finalizeExport($node);
		return $node;
	}
	
	/**
	 * 
	 * @param ComhonObject|null $object
	 * @param string $nodeName
	 * @param Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($object)) {
			return null;
		}
		$node              = $interfacer->createNode($nodeName);
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$onlyUpdatedValues = $isFirstLevel && $interfacer->hasToExportOnlyUpdatedValues();
		$propertiesFilter  = $interfacer->getPropertiesFilter($object->getModel()->getName());
		
		if (array_key_exists(spl_object_hash($object), self::$instanceObjectHash)) {
			if (self::$instanceObjectHash[spl_object_hash($object)] > 0) {
				throw new \Exception("Loop detected. Object '{$object->getModel()->getName()}' can't be exported");
			}
		} else {
			self::$instanceObjectHash[spl_object_hash($object)] = 0;
		}
		self::$instanceObjectHash[spl_object_hash($object)]++;
		$properties = $object->getModel()->getSpecificProperties($private, $isSerialContext);
		foreach ($object->getValues() as $propertyName => $value) {
			if (array_key_exists($propertyName, $properties)) {
				$property = $properties[$propertyName];
				
				if ($property->isExportable($private, $isSerialContext, $value)) {
					if ((!$onlyUpdatedValues || $property->isId() || $object->isUpdatedValue($propertyName))
						&& (is_null($propertiesFilter) || array_key_exists($propertyName, $propertiesFilter))) {
						$propertyName  = $isSerialContext ? $property->getSerializationName() : $propertyName;
						$exportedValue = $property->getModel()->_export($value, $propertyName, $interfacer, false);
						$interfacer->setValue($node, $exportedValue, $propertyName, $property->isInterfacedAsNodeXml());
					}
					else if ($property->isForeign() && $interfacer->hasToExportMainForeignObjects() && !is_null($value)) {
						$property->getModel()->_export($value, $value->getModel()->getName(), $interfacer, false);
					}
				}
				else if ($isSerialContext && $property->isAggregation() && $interfacer->hasToExportMainForeignObjects() && !is_null($value)) {
					$property->getModel()->_export($value, $value->getModel()->getName(), $interfacer, false);
				}
			}
		}
		if ($isSerialContext) {
			foreach ($object->getModel()->multipleForeignProperties as $propertyName => $multipleForeignProperty) {
				$foreignObject = $object->getValue($propertyName);
				if (!is_null($foreignObject) && $multipleForeignProperty->getModel()->verifValue($foreignObject)) {
					if (!$foreignObject->hasCompleteId()) {
						throw new \Exception("Warning cannot export id of foreign property with model '{$this->modelName}' because object doesn't have complete id");
					}
					foreach ($multipleForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
						if (!$onlyUpdatedValues || $foreignObject->isUpdatedValue($idProperty->getName())) {
							$idValue = $foreignObject->getValue($idProperty->getName());
							$idProperty->getModel()->verifValue($idValue);
							$idValue = $idProperty->getModel()->_export($idValue, $serializationName, $interfacer, false);
							$interfacer->setValue($node, $idValue, $serializationName);
						}
					}
				}
			}
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_flattenValues($node, $object, $interfacer);
		}
		if ($object->getModel() !== $this) {
			if (!$object->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$interfacer->setValue($node, $object->getModel()->getName(), Interfacer::INHERITANCE_KEY);
		}
		self::$instanceObjectHash[spl_object_hash($object)]--;
		return $node;
	}
	
	/**
	 * 
	 * @param mixed $node
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 */
	protected function _flattenValues(&$node, ComhonObject $object, Interfacer $interfacer) {
		foreach ($object->getModel()->getComplexProperties() as $propertyName => $complexProperty) {
			$interfacedPropertyName = $interfacer->isSerialContext() ? $complexProperty->getSerializationName() : $propertyName;
			
			if (!$complexProperty->isForeign() || ($object->getValue($propertyName) instanceof ObjectArray)) {
				$interfacer->flattenNode($node, $interfacedPropertyName);
			}
			else if ($interfacer->isComplexInterfacedId($interfacer->getValue($node, $interfacedPropertyName, true))) {
				$foreignObject = $object->getValue($propertyName);
				if ($foreignObject->getModel() instanceof MainModel) {
					$interfacer->replaceValue($node, $interfacedPropertyName, $foreignObject->getId());
				} else {
					$interfacer->flattenNode($node, $interfacedPropertyName);
				}
			}
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
		if ($object->getModel() !== $this) {
			if (!$object->getModel()->isInheritedFrom($this)) {
				throw new \Exception('object doesn\'t have good model');
			}
			$objectId = $interfacer->createNode($nodeName);
			$interfacer->setValue($objectId, $object->getModel()->_toInterfacedId($object, $interfacer), Interfacer::COMPLEX_ID_KEY);
			$interfacer->setValue($objectId, $object->getModel()->getName(), Interfacer::INHERITANCE_KEY);
			return $objectId;
		}
		return $this->_toInterfacedId($object, $interfacer);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 */
	public function fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer) {
		throw new \Exception('can\'t apply function fillObject(). Only callable for MainModel');
	}
	
	/**
	 *
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return ComhonObject
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		throw new \Exception('can\'t apply function import(). Only callable for MainModel');
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
		throw new \Exception('can\'t apply function _importFromObjectArray(). Only callable for MainModel');
	}
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @return ComhonObject
	 */
	protected function _getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		$inheritance = $interfacer->getValue($interfacedObject, Interfacer::INHERITANCE_KEY);
		$model = is_null($inheritance) ? $this : $this->_getIneritedModel($inheritance, $parentMainModel);
		$id = $model->getIdFromInterfacedObject($interfacedObject, $interfacer);
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, $localObjectCollection, $isFirstLevel);
	}
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @return NULL
	 */
	public function getIdFromInterfacedObject($interfacedObject, Interfacer $interfacer) {
		$isSerialContext = $interfacer->isSerialContext();
		$private = $interfacer->isPrivateContext();
		if (!is_null($this->uniqueIdProperty)) {
			if (!$this->uniqueIdProperty->isInterfaceable($private, $isSerialContext)) {
				return null;
			}
			$propertyName = $isSerialContext ? $this->uniqueIdProperty->getSerializationName() : $this->uniqueIdProperty->getName();
			$id = $interfacer->getValue($interfacedObject, $propertyName, $this->uniqueIdProperty->isInterfacedAsNodeXml());
			return $this->uniqueIdProperty->getModel()->importSimple($id, $interfacer);
		}
		$idValues = [];
		foreach ($this->getIdProperties() as $idProperty) {
			if ($idProperty->isInterfaceable($private, $isSerialContext)) {
				$propertyName = $isSerialContext ? $idProperty->getSerializationName() : $idProperty->getName();
				$idValue = $interfacer->getValue($interfacedObject, $propertyName, $idProperty->isInterfacedAsNodeXml());
				$idValues[] = $idProperty->getModel()->importSimple($idValue, $interfacer);
			} else {
				$idValues[] = null;
			}
		}
		return $this->encodeId($idValues);
	}
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @return ComhonObject|null
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new \Exception('unexpeted value type');
			}
		}
		$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, $parentMainModel, $isFirstLevel);
		$this->_fillObject($object, $interfacedObject, $interfacer, $localObjectCollection, $parentMainModel, $isFirstLevel);
		return $object;
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 */
	protected function _fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		$model = $object->getModel();
		if ($model !== $this && !$model->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_unFlattenValues($interfacedObject, $object, $interfacer);
		}
		if ($this instanceof MainModel) {
			$parentMainModel = $this;
		}
		
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$flagAsUpdated     = $interfacer->hasToFlagValuesAsUpdated();
		$properties        = $model->getSpecificProperties($private, $isSerialContext);
		
		foreach ($properties as $propertyName => $property) {
			if ($property->isInterfaceable($private, $isSerialContext)) {
				$interfacedPropertyName = $isSerialContext ? $property->getSerializationName() : $propertyName;
				if ($interfacer->hasValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml())) {
					$value = $interfacer->getValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml());
					$value = $interfacer->isNullValue($value) ? null
						: $property->getModel()->_import($value, $interfacer, $localObjectCollection, $parentMainModel);
					$object->setValue($propertyName, $value, $flagAsUpdated);
				}
			}
		}
		if ($isSerialContext) {
			foreach ($model->multipleForeignProperties as $propertyName => $multipleForeignProperty) {
				$id = [];
				foreach ($multipleForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
					if ($interfacer->hasValue($interfacedObject, $serializationName)) {
						$idPart = $interfacer->getValue($interfacedObject, $serializationName);
						if ($interfacer instanceof NoScalarTypedInterfacer) {
							$idPart = $interfacer->isNullValue($value) ? null
								: $idProperty->getModel()->importSimple($idPart, $interfacer);
						}
						$id[] = $idPart;
					}
				}
				if (count($id) !== count($multipleForeignProperty->getMultipleIdProperties())) {
					throw new \Exception('not complete multiple id foreign value');
				}
				$value = $multipleForeignProperty->getModel()->_import(json_encode($id), $interfacer, $localObjectCollection, $parentMainModel);
				$object->setValue($propertyName, $value, $flagAsUpdated);
			}
		}
	}
	
	/**
	 *
	 * @param mixed $node
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 */
	protected function _unFlattenValues(&$node, ComhonObject $object, Interfacer $interfacer) {
		foreach ($object->getModel()->getComplexProperties() as $propertyName => $complexProperty) {
			$interfacedPropertyName = $interfacer->isSerialContext() ? $complexProperty->getSerializationName() : $propertyName;
			
			if (!$complexProperty->isForeign() || $complexProperty->getModel()->getModel() instanceof ModelArray) {
				$interfacer->unFlattenNode($node, $interfacedPropertyName);
			}
			else if ($interfacer->isFlattenComplexInterfacedId($interfacer->getValue($node, $interfacedPropertyName, true))) {
				$interfacer->unFlattenNode($node, $interfacedPropertyName);
			}
		}
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @return ComhonObject
	 */
	protected function _importId($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel) {
		if ($interfacer->isNullValue($value)) {
			return null;
		}
		if ($interfacer->isComplexInterfacedId($value)) {
			if (!$interfacer->hasValue($value, Interfacer::COMPLEX_ID_KEY) || !$interfacer->hasValue($value, Interfacer::INHERITANCE_KEY)) {
				throw new \Exception('object id must have property \''.Interfacer::COMPLEX_ID_KEY.'\' and \''.Interfacer::INHERITANCE_KEY.'\'');
			}
			$id = $interfacer->getValue($value, Interfacer::COMPLEX_ID_KEY);
			$inheritance = $interfacer->getValue($value, Interfacer::INHERITANCE_KEY);
			$model = $this->_getIneritedModel($inheritance, $parentMainModel);
		}
		else {
			$id = $value;
			$model = $this;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			/** @var SimpleModel $model */
			if ($model->hasUniqueIdProperty()) {
				$id = $model->getUniqueIdProperty()->getModel()->importSimple($id, $interfacer);
			} else if (!is_string($id)) {
				$id = $interfacer->castValueToString($id);
			}
		}
		if (is_null($id)) {
			return null;
		}
		if (is_object($id) || is_array($id) || $id === '') {
			$id = is_object($id) || is_array($id) ? json_encode($id) : $id;
			throw new \Exception("malformed id '$id' for model '{$this->modelName}'");
		}
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, $localObjectCollection, false, true);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return integer|string
	 */
	public function _toInterfacedId(ComhonObject $object, Interfacer $interfacer) {
		if (!$object->hasCompleteId()) {
			throw new \Exception("Warning cannot export id of foreign property with model '{$this->modelName}' because object doesn't have complete id");
		}
		return $object->getId();
	}
	
	protected function _buildObjectFromId($id, $isloaded, $flagAsUpdated) {
		return $this->_fillObjectwithId($this->getObjectInstance($isloaded), $id, $flagAsUpdated);
	}
	
	protected function _fillObjectwithId(ComhonObject $object, $id, $flagAsUpdated) {
		if ($object->getModel() !== $this) {
			throw new \Exception("object doesn't have good model. {$this->getName()} expected, {$object->getModel()->getName()} given");
		}
		if (!is_null($id)) {
			$object->setId($id, $flagAsUpdated);
		}
		return $object;
	}
	
	/**
	 * @param ComhonObject $value
	 */
	public function verifValue($value) {
		if (!($value instanceof ComhonObject) || ($value->getModel() !== $this && !$value->getModel()->isInheritedFrom($this))) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be an instance of $this->objectClass, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
}
