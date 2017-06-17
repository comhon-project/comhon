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
use Comhon\Serialization\SerializationUnit;
use Comhon\Object\ObjectUnique;

abstract class Model {

	/**
	 * @var integer[] array used to avoid infinite loop when objects are visited
	 */
	private static $instanceObjectHash = [];
	
	/** @var string */
	protected $modelName;
	
	/** @var boolean */
	protected $isLoaded = false;
	
	/** @var boolean */
	protected $isLoading = false;
	
	/** @var Model */
	private $parent;
	
	/** @var string */
	private $objectClass = 'Comhon\Object\Object';
	
	/** @var boolean */
	private $isExtended = false;
	
	/** @var \Comhon\Model\Property\Property[] */
	private $properties   = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $idProperties = [];
	
	/** @var \Comhon\Model\Property\AggregationProperty[] */
	private $aggregations = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $publicProperties  = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $serializableProperties = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $propertiesWithDefaultValues = [];
	
	/** @var \Comhon\Model\Property\MultipleForeignProperty[] */
	private $multipleForeignProperties = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $complexProperties = [];
	
	/** @var \Comhon\Model\Property\Property[] */
	private $dateTimeProperties = [];
	
	/** @var Property */
	private $uniqueIdProperty;
	
	/** @var boolean */
	private $hasPrivateIdProperty;
	
	/**
	 * don't instanciate a model by yourself because it take time.
	 * to get a model instance use singleton ModelManager.
	 * 
	 * @param string $modelName
	 * @param boolean $loadModel
	 */
	public function __construct($modelName, $loadModel) {
		$this->modelName = $modelName;
		if ($loadModel) {
			$this->load();
		}
	}
	
	/**
	 * load model
	 * 
	 * parse related manifest, fill model with needed inofmrations
	 */
	final public function load() {
		if (!$this->isLoaded && !$this->isLoading) {
			$this->isLoading = true;
			$result = ModelManager::getInstance()->getProperties($this);
			$this->parent = $result[ModelManager::PARENT_MODEL];
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
	
	/**
	 * set differents properties
	 *
	 * @param \Comhon\Model\Property\Property[] $properties
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
	
	/**
	 * load, build and affect serializaton to model
	 */
	protected function _setSerialization() {}
	
	/**
	 * initialize some informations not managed by generic load
	 */
	protected function _init() {
		// you can overide this function in inherited class to initialize others attributes
	}
	
	/**
	 * get full qualified class name of object associated to model
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return $this->objectClass;
	}
	
	/**
	 * get instance of object associated to model
	 * 
	 * @param boolean $isloaded define if instanciated object will be flaged as loaded or not
	 * @return \Comhon\Object\ObjectUnique|\Comhon\Object\ObjectArray
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
	
	/**
	 * get parent model if current model extends from another one
	 * 
	 * @return Model|null null if no parent model
	 */
	public function getParent() {
		return $this->parent;
	}
	
	/**
	 * verify if model extends from another one
	 * 
	 * @return boolean
	 */
	public function hasParent() {
		return !is_null($this->parent);
	}
	
	/**
	 * verify if current model inherit from specified model
	 * 
	 * @param Model $model
	 * @return boolean
	 */
	public function isInheritedFrom(Model $model) {
		$currentModel = $this;
		$isInherited = false;
		while (!is_null($currentModel->parent) && !$isInherited) {
			$isInherited = $model === $currentModel->parent;
			$currentModel = $currentModel->parent;
		}
		return $isInherited;
	}
	
	/**
	 * get or create an instance of ComhonObject
	 * 
	 * @param integer|string $id
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @param boolean $isForeign
	 * @throws \Exception
	 * @return \Comhon\Object\ComhonObject
	 */
	protected function _getOrCreateObjectInstance($id, Interfacer $interfacer, $localObjectCollection, $isFirstLevel, $isForeign = false) {
		throw new \Exception('can\'t apply function. Only callable for MainModel or LocalModel');
	}
	
	/**
	 * get model name
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->modelName;
	}
	
	/**
	 * get model name
	 * 
	 * @return string
	 */
	public function getMainModelName() {
		return $this->modelName;
	}
	
	/**
	 * get model properties
	 * 
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getProperties() {
		return $this->properties;
	}
	
	/**
	 * get model properties according private/public context
	 *
	 * @param boolean $private
	 * @return \Comhon\Model\Property\Property[]
	 */
	protected function _getContextProperties($private) {
		return $private ? $this->properties : $this->publicProperties;
	}
	
	/**
	 * get model complex properties i.e. properties with model different than SimpleModel
	 *
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getComplexProperties() {
		return $this->complexProperties;
	}
	
	/**
	 * get model properties with dateTime model
	 *
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getDateTimeProperties() {
		return $this->dateTimeProperties;
	}
	
	/**
	 * get model public properties
	 *
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getPublicProperties() {
		return $this->publicProperties;
	}
	
	/**
	 * get model properties names
	 *
	 * @return string[]
	 */
	public function getPropertiesNames() {
		return array_keys($this->properties);
	}
	
	/**
	 * get property according specified name
	 * 
	 * @param string $propertyName
	 * @param boolean $throwException if true, throw an exception if property doesn't exist
	 * @throws \Comhon\Exception\PropertyException
	 * @return \Comhon\Model\Property\Property|null 
	 *     null if property with specified name doesn't exist
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
	 * get id property according specified name
	 *
	 * @param string $propertyName
	 * @param boolean $throwException
	 * @throws \Comhon\Exception\PropertyException
	 * @return \Comhon\Model\Property\Property|null 
	 *     null if property with specified name doesn't exist
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
	 * verify if model has property with specified name
	 * 
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName) {
		return array_key_exists($propertyName, $this->properties);
	}
	
	/**
	 * verify if model has id property with specified name
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasIdProperty($propertyName) {
		return array_key_exists($propertyName, $this->idProperties);
	}
	
	/**
	 * get foreign properties that have their own serialization
	 * 
	 * @param string $serializationType ("sqlTable", "jsonFile"...)
	 * @return \Comhon\Model\Property\Property[]
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
	
	/**
	 * get serializable properties
	 * 
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getSerializableProperties() {
		return $this->serializableProperties;
	}
	
	/**
	 * get id properties
	 * 
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getIdProperties() {
		return $this->idProperties;
	}
	
	/**
	 * get id property if there is one and only one id property
	 * 
	 * @return \Comhon\Model\Property\Property|null 
	 *            null if there is no id property or there are several id properties
	 */
	public function getUniqueIdProperty() {
		return $this->uniqueIdProperty;
	}
	
	/**
	 * verify if there is one and only one id property
	 * 
	 * @return boolean
	 */
	public function hasUniqueIdProperty() {
		return !is_null($this->uniqueIdProperty);
	}
	
	/**
	 * verify if model has at least one private id property
	 * 
	 * @return boolean
	 */
	public function hasPrivateIdProperty() {
		return $this->hasPrivateIdProperty;
	}
	
	/**
	 * verify if model has at least one id property
	 *
	 * @return boolean
	 */
	public function hasIdProperties() {
		return !empty($this->idProperties);
	}
	
	/**
	 * get properties with default value
	 * 
	 * @return \Comhon\Model\Property\Property
	 */
	public function getPropertiesWithDefaultValues() {
		return $this->propertiesWithDefaultValues;
	}
	
	/**
	 * get aggregation proprties
	 * 
	 * @return \Comhon\Model\Property\AggregationProperty[]:
	 */
	public function getAggregationProperties() {
		return $this->aggregations;
	}
	
	/**
	 * get first id property if model has at least one id property
	 * 
	 * @return \Comhon\Model\Property\Property|null
	 */
	public function getFirstIdProperty() {
		reset($this->idProperties);
		return empty($this->idProperties) ? null : current($this->idProperties);
	}
	
	/**
	 * verify if model is loaded or not
	 * 
	 * @return boolean
	 */
	public function isLoaded() {
		return $this->isLoaded;
	}
	
	/**
	 * verify if model is complex or not
	 * 
	 * model is complex if model is not instance of SimpleModel
	 * 
	 * @return boolean
	 */
	public function isComplex() {
		return true;
	}
	
	/**
	 * get serialization linked to model
	 * 
	 * @return \Comhon\Serialization\SerializationUnit|null
	 */
	public function getSerialization() {
		return null;
	}
	
	/**
	 * verify if model has linked serialization with specified type
	 * 
	 * @param string $serializationType
	 * @return boolean
	 */
	public function hasSerializationUnit($serializationType) {
		return false;
	}
	
	/**
	 * get serialization settings (if model has linked serialzation)
	 * 
	 * @return \Comhon\Object\ObjectUnique|null null if no serialization settings
	 */
	public function getSerializationSettings() {
		return null;
	}
	
	/**
	 * verify if model has linked sql serialization
	 *
	 * @return boolean
	 */
	public function hasSqlTableUnit() {
		return false;
	}
	
	/**
	 * get linked sql serialization (if model has linked sql serialzation)
	 *
	 * @return \Comhon\Serialization\SqlTable|null null if no sql serialization
	 */
	public function getSqlTableUnit() {
		return null;
	}
	
	/**
	 * encode multiple ids in json format
	 * 
	 * @param array $idValues 
	 * @return string
	 */
	public function encodeId($idValues) {
		return empty($idValues) ? null : json_encode($idValues);
	}
	
	/**
	 * decode multiple ids from json format
	 * 
	 * @param string $id
	 * @return array
	 */
	public function decodeId($id) {
		$decodedId = json_decode($id);
		if (!is_array($decodedId) || (count($this->getIdProperties()) !== count($decodedId))) {
			throw new \Exception("id invalid : $id");
		}
		return $decodedId;
	}
	
	/**
	 * add main current object to main foreign objects list in interfacer
	 * 
	 * object is added only if it has a main model associated
	 * avoid to re-export current object via export of main foreign object
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _addMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if (!($object instanceof ObjectUnique)) {
			throw new \Exception('first parameter should be ObjectUnique');
		}
		if ($interfacer->hasToExportMainForeignObjects() && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->addMainForeignObject($interfacer->createNode('empty'), $object->getId(), $object->getModel());
		}
	}
	
	/**
	 * remove main current object from main foreign objects list in interfacer previously added
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _removeMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if (!($object instanceof ObjectUnique)) {
			throw new \Exception('first parameter should be ObjectUnique');
		}
		if ($interfacer->hasToExportMainForeignObjects() && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->removeMainForeignObject($object->getId(), $object->getModel());
		}
	}
	
	/**
	 * export comhon object in specified format
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	final public function export(ComhonObject $object, Interfacer $interfacer) {
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
	 * export comhon object in specified format
	 * 
	 * @param \Comhon\Object\ComhonObject|null $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
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
		$properties = $object->getModel()->_getContextProperties($private);
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
	 * flatten complex values of specified node
	 * 
	 * @param mixed $node
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _flattenValues(&$node, ObjectUnique $object, Interfacer $interfacer) {
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
	 * export comhon object id
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
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
	 * fill comhon object with values from interfaced object
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	public function fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer) {
		throw new \Exception('can\'t apply function fillObject(). Only callable for MainModel');
	}
	
	/**
	 * import interfaced object 
	 * 
	 * build comhon object with values from interfaced object
	 * import may create an object or update an existing object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return \Comhon\Object\ComhonObject
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		throw new \Exception('can\'t apply function import(). Only callable for MainModel');
	}
	
	/**
	 * import interfaced object related to a main model
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _importMain($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection) {
		throw new \Exception('can\'t apply function _importMain(). Only callable for MainModel');
	}
	
	/**
	 * get comhon object instance according model and interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param MainModel $mainModelContainer
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		$inheritance = $interfacer->getValue($interfacedObject, Interfacer::INHERITANCE_KEY);
		$model = is_null($inheritance) ? $this : $this->_getIneritedModel($inheritance, $mainModelContainer);
		$id = $model->getIdFromInterfacedObject($interfacedObject, $interfacer);
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, $localObjectCollection, $isFirstLevel);
	}
	
	/**
	 * get id from interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
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
	 * import interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param MainModel $mainModelContainer
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique|null
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
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
		$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, $mainModelContainer, $isFirstLevel);
		$this->_fillObject($object, $interfacedObject, $interfacer, $localObjectCollection, $mainModelContainer, $isFirstLevel);
		return $object;
	}
	
	/**
	 * fill comhon object with values from interfaced object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param MainModel $mainModelContainer
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 */
	protected function _fillObject(ObjectUnique $object, $interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		$model = $object->getModel();
		if ($model !== $this && !$model->isInheritedFrom($this)) {
			throw new \Exception('object doesn\'t have good model');
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_unFlattenValues($interfacedObject, $object, $interfacer);
		}
		if ($this instanceof MainModel) {
			$mainModelContainer = $this;
		}
		
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$flagAsUpdated     = $interfacer->hasToFlagValuesAsUpdated();
		$properties        = $model->_getContextProperties($private);
		
		foreach ($properties as $propertyName => $property) {
			if ($property->isInterfaceable($private, $isSerialContext)) {
				$interfacedPropertyName = $isSerialContext ? $property->getSerializationName() : $propertyName;
				if ($interfacer->hasValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml())) {
					$value = $interfacer->getValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml());
					$value = $interfacer->isNullValue($value) ? null
						: $property->getModel()->_import($value, $interfacer, $localObjectCollection, $mainModelContainer);
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
				$value = $multipleForeignProperty->getModel()->_import(json_encode($id), $interfacer, $localObjectCollection, $mainModelContainer);
				$object->setValue($propertyName, $value, $flagAsUpdated);
			}
		}
	}
	
	/**
	 * unflatten complex values from interfaced object
	 *
	 * @param mixed $node
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _unFlattenValues(&$node, ObjectUnique $object, Interfacer $interfacer) {
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
	 * create or get comhon object according interfaced id
	 *
	 * @param mixed $interfacedId
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param MainModel $mainModelContainer
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _importId($interfacedId, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer) {
		if ($interfacer->isNullValue($interfacedId)) {
			return null;
		}
		if ($interfacer->isComplexInterfacedId($interfacedId)) {
			if (!$interfacer->hasValue($interfacedId, Interfacer::COMPLEX_ID_KEY) || !$interfacer->hasValue($interfacedId, Interfacer::INHERITANCE_KEY)) {
				throw new \Exception('object id must have property \''.Interfacer::COMPLEX_ID_KEY.'\' and \''.Interfacer::INHERITANCE_KEY.'\'');
			}
			$id = $interfacer->getValue($interfacedId, Interfacer::COMPLEX_ID_KEY);
			$inheritance = $interfacer->getValue($interfacedId, Interfacer::INHERITANCE_KEY);
			$model = $this->_getIneritedModel($inheritance, $mainModelContainer);
		}
		else {
			$id = $interfacedId;
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
	 * build interface id from comhon object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return integer|string
	 */
	public function _toInterfacedId(ObjectUnique $object, Interfacer $interfacer) {
		if (!$object->hasCompleteId()) {
			throw new \Exception("Warning cannot export id of foreign property with model '{$this->modelName}' because object doesn't have complete id");
		}
		return $object->getId();
	}
	
	/**
	 * create comhon object and fill it with id
	 * 
	 * @param mixed $id
	 * @param boolean $isloaded
	 * @param boolean $flagAsUpdated
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _buildObjectFromId($id, $isloaded, $flagAsUpdated) {
		return $this->_fillObjectwithId($this->getObjectInstance($isloaded), $id, $flagAsUpdated);
	}
	
	/**
	 * fill comhon object with id
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _fillObjectwithId(ObjectUnique $object, $id, $flagAsUpdated) {
		if ($object->getModel() !== $this) {
			throw new \Exception("object doesn't have good model. {$this->getName()} expected, {$object->getModel()->getName()} given");
		}
		if (!is_null($id)) {
			$object->setId($id, $flagAsUpdated);
		}
		return $object;
	}
	
	/**
	 * verify if value is correct according current model
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!($value instanceof ObjectUnique) || ($value->getModel() !== $this && !$value->getModel()->isInheritedFrom($this))) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be an instance of $this->objectClass, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
}
