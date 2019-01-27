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
use Comhon\Exception\UndefinedPropertyException;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Serialization\SerializationUnit;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\UnexpectedModelException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Visitor\ObjectCollectionCreator;
use Comhon\Exception\CastComhonObjectException;

class Model extends ModelComplex implements ModelUnique, ModelComhonObject {

	/** @var boolean */
	protected $isLoading = false;
	
	/** @var Model */
	private $parent;
	
	/** @var string */
	private $objectClass = Object::class;
	
	/** @var boolean */
	private $isExtended = false;
	
	/** @var boolean */
	private $isMain;
	
	/** @var SerializationUnit */
	private $serialization = null;
	
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
	private $hasPrivateIdProperty = false;
	
	/**
	 * don't instanciate a model by yourself because it take time.
	 * to get a model instance use singleton ModelManager.
	 * 
	 * @param string $modelName
	 */
	public function __construct($modelName) {
		$this->modelName = $modelName;
	}
	
	/**
	 * load model
	 * 
	 * parse related manifest, fill model with needed inofmrations
	 */
	final public function load() {
		if (!$this->isLoaded && !$this->isLoading) {
			try {
				$this->isLoading = true;
				$result = ModelManager::getInstance()->getProperties($this);
				$this->isMain = $result[ModelManager::IS_MAIN_MODEL];
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
				
			} catch (\Exception $e) {
				// reinitialize attributes if any excpetion
				$this->isLoading = false;
				$this->parent = null;
				$this->objectClass = Object::class;
				$this->isExtended = false;
				$this->properties   = [];
				$this->idProperties = [];
				$this->aggregations = [];
				$this->publicProperties  = [];
				$this->serializableProperties = [];
				$this->propertiesWithDefaultValues = [];
				$this->multipleForeignProperties = [];
				$this->complexProperties = [];
				$this->dateTimeProperties = [];
				$this->uniqueIdProperty = null;
				$this->hasPrivateIdProperty = false;
				
				throw $e;
			}
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
	final protected function _setSerialization() {
		$this->serialization = ModelManager::getInstance()->getSerializationInstance($this);
		if ($this->hasParent()) {
			if (count($this->getIdProperties()) != count($this->getParent()->getIdProperties())) {
				throw new ComhonException("model {$this->getName()} extended from model {$this->getParent()->getName()} and with same serialization must have same id(s)");
			}
			foreach ($this->getParent()->getIdProperties() as $propertyName => $property) {
				if (!$this->hasIdProperty($propertyName) || !$property->isEqual($this->getIdProperty($propertyName))) {
					throw new ComhonException("model {$this->getName()} extended from model {$this->getParent()->getName()} and with same serialization must have same id(s)");
				}
			}
		}
	}
	
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
				throw new UnexpectedModelException($this, $object->getModel());
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
	 * get model name
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->modelName;
	}
	
	/**
	 * get namespace of model
	 *
	 * @return string
	 */
	public function getNameSpace() {
		$name = $this->getName();
		if (($pos = strrpos($name, '\\')) !== false) {
			return substr($name, 0, $pos + 1);
		}
		return '';
	}
	
	/**
	 * get short name of model (name without namespace)
	 *
	 * @return string
	 */
	public function getShortName() {
		$name = $this->getName();
		if (($pos = strrpos($name, '\\')) !== false) {
			return substr($name, $pos + 1);
		}
		return $name;
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
	 * @throws \Comhon\Exception\UndefinedPropertyException
	 * @return \Comhon\Model\Property\Property|null 
	 *     null if property with specified name doesn't exist
	 */
	public function getProperty($propertyName, $throwException = false) {
		if ($this->hasProperty($propertyName)) {
			return $this->properties[$propertyName];
		}
		else if ($throwException) {
			throw new UndefinedPropertyException($this, $propertyName);
		}
		return null;
	}
	
	/**
	 * get id property according specified name
	 *
	 * @param string $propertyName
	 * @param boolean $throwException
	 * @throws \Comhon\Exception\UndefinedPropertyException
	 * @return \Comhon\Model\Property\Property|null 
	 *     null if property with specified name doesn't exist
	 */
	public function getIdProperty($propertyName, $throwException = false) {
		if ($this->hasIdProperty($propertyName)) {
			return $this->idProperties[$propertyName];
		}
		else if ($throwException) {
			throw new UndefinedPropertyException($this, $propertyName);
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
	 * verify if model is a main model
	 *
	 * @return boolean
	 */
	public function isMain() {
		return $this->isMain;
	}
	
	/**
	 * get serialization linked to model
	 * 
	 * @return \Comhon\Serialization\SerializationUnit|null
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
	 * verify if model has serialization with specified type
	 * 
	 * @param string $serializationType
	 * @return boolean
	 */
	public function hasSerializationUnit($serializationType) {
		return !is_null($this->serialization) && ($this->serialization->getType() == $serializationType);
	}
	
	/**
	 * get serialization settings (if model has linked serialzation)
	 * 
	 * @return \Comhon\Object\ObjectUnique|null null if no serialization settings
	 */
	public function getSerializationSettings() {
		return is_null($this->serialization) ? null : $this->serialization->getSettings();
	}
	
	/**
	 * verify if model has linked sql serialization
	 *
	 * @return boolean
	 */
	public function hasSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable);
	}
	
	/**
	 * get linked sql serialization (if model has linked sql serialzation)
	 *
	 * @return \Comhon\Serialization\SqlTable|null null if no sql serialization
	 */
	public function getSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization instanceof SqlTable) ? $this->serialization : null;
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
			throw new ComhonException("id invalid : $id");
		}
		return $decodedId;
	}
	
	/**
	 * verify if during import we stay in first level object or not
	 * 
	 * @param boolean $isCurrentLevelFirstLevel
	 * @return boolean
	 */
	protected function _isNextLevelFirstLevel($isCurrentLevelFirstLevel) {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_addMainCurrentObject()
	 */
	protected function _addMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if (!($object instanceof ObjectUnique)) {
			throw new ArgumentException($object, ObjectUnique::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects() && $object->getModel()->isMain() && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->addMainForeignObject($interfacer->createNode('empty'), $object->getId(), $object->getModel());
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_removeMainCurrentObject()
	 */
	protected function _removeMainCurrentObject(ComhonObject $object, Interfacer $interfacer) {
		if (!($object instanceof ObjectUnique)) {
			throw new ArgumentException($object, ObjectUnique::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects() && $object->getModel()->isMain() && !is_null($object->getId()) && $object->hasCompleteId()) {
			$interfacer->removeMainForeignObject($object->getId(), $object->getModel());
		}
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
		if (is_null($this->getSerialization())) {
			throw new ComhonException("model {$this->getName()} doesn't have serialization");
		}
		$this->load();
		if (!$this->hasIdProperties()) {
			throw new ComhonException("model '$this->modelName' must have at least one id property to load object");
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
		} catch (CastComhonObjectException $e) {
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
			throw new ComhonException("model {$this->getName()} doesn't have serialization");
		}
		if (!$object->isLoaded() || $forceLoad) {
			$success = $serializationUnit->loadObject($object, $propertiesFilter);
		}
		return $success;
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
				throw new ComhonException("Loop detected. Object '{$object->getModel()->getName()}' can't be exported");
			}
		} else {
			self::$instanceObjectHash[spl_object_hash($object)] = 0;
		}
		self::$instanceObjectHash[spl_object_hash($object)]++;
		$properties = $object->getModel()->_getContextProperties($private);
		foreach ($object->getValues() as $propertyName => $value) {
			try {
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
							$property->getModel()->_export($value, $value->getUniqueModel()->getShortName(), $interfacer, false);
						}
					}
					else if ($isSerialContext && $property->isAggregation() && $interfacer->hasToExportMainForeignObjects() && !is_null($value)) {
						$property->getModel()->_export($value, $value->getUniqueModel()->getShortName(), $interfacer, false);
					}
				}
			} catch (ComhonException $e) {
				throw new ExportException($e, $propertyName);
			}
		}
		if ($isSerialContext) {
			foreach ($object->getModel()->multipleForeignProperties as $propertyName => $multipleForeignProperty) {
				try {
					$foreignObject = $object->getValue($propertyName);
					if (!is_null($foreignObject)) {
						if (!$foreignObject->hasCompleteId()) {
							throw new ComhonException("cannot export id of foreign property with model '{$this->modelName}' because object doesn't have complete id");
						}
						foreach ($multipleForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
							if (!$onlyUpdatedValues || $foreignObject->isUpdatedValue($idProperty->getName())) {
								$idValue = $foreignObject->getValue($idProperty->getName());
								$idValue = $idProperty->getModel()->_export($idValue, $serializationName, $interfacer, false);
								$interfacer->setValue($node, $idValue, $serializationName);
							}
						}
					}
				} catch (ComhonException $e) {
					throw new ExportException($e, $propertyName);
				}
			}
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_flattenValues($node, $object, $interfacer);
		}
		if ($object->getModel() !== $this) {
			if (!$object->getModel()->isInheritedFrom($this)) {
				throw new UnexpectedModelException($this, $object->getModel());
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
				if ($foreignObject->getModel()->isMain()) {
					$interfacer->replaceValue($node, $interfacedPropertyName, $foreignObject->getId());
				} else {
					$interfacer->flattenNode($node, $interfacedPropertyName);
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_exportId()
	 */
	protected function _exportId(ComhonObject $object, $nodeName, Interfacer $interfacer) {
		if ($object->getModel() !== $this) {
			if (!$object->getModel()->isInheritedFrom($this)) {
				throw new UnexpectedModelException($this, $object->getModel());
			}
			$exportedId = $interfacer->createNode($nodeName);
			$interfacer->setValue($exportedId, $object->getModel()->_toInterfacedId($object, $interfacer), Interfacer::COMPLEX_ID_KEY);
			$interfacer->setValue($exportedId, $object->getModel()->getName(), Interfacer::INHERITANCE_KEY);
		} else {
			$exportedId = $this->_toInterfacedId($object, $interfacer);
		}
		
		if ($this->isMain && $interfacer->hasToExportMainForeignObjects()) {
			if ($object->getModel() === $this) {
				$model = $this;
			} else {
				if (!$object->getModel()->isInheritedFrom($this)) {
					throw new UnexpectedModelException($this, $object->getModel());
				}
				$model = $object->getModel();
			}
			$valueId   = $this->_toInterfacedId($object, $interfacer);
			$modelName = $model->getName();
			
			if (!$interfacer->hasMainForeignObject($modelName, $valueId)) {
				$interfacer->addMainForeignObject($interfacer->createNode('empty'), $valueId, $object->getModel());
				$interfacer->addMainForeignObject($model->_export($object, 'root', $interfacer, true), $valueId, $object->getModel());
			}
		}
		return $exportedId;
	}
	
	/**
	 * get inherited model
	 *
	 * @param string $inheritanceModelName
	 * @return Model;
	 */
	protected function _getIneritedModel($inheritanceModelName) {
		$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
		if (!$model->isInheritedFrom($this)) {
			throw new UnexpectedModelException($this, $model);
		}
		return $model;
	}
	
	/**
	 * get id from interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @return mixed
	 */
	public function getIdFromInterfacedObject($interfacedObject, Interfacer $interfacer, $isFirstLevel) {
		$isSerialContext = $interfacer->isSerialContext();
		$private = $interfacer->isPrivateContext();
		if (!is_null($this->uniqueIdProperty)) {
			if (!$this->uniqueIdProperty->isInterfaceable($private, $isSerialContext)) {
				return null;
			}
			$propertyName = $isSerialContext ? $this->uniqueIdProperty->getSerializationName() : $this->uniqueIdProperty->getName();
			$id = $interfacer->getValue($interfacedObject, $propertyName, $this->uniqueIdProperty->isInterfacedAsNodeXml());
			return $this->uniqueIdProperty->getModel()->importSimple($id, $interfacer, $isFirstLevel);
		}
		$idValues = [];
		foreach ($this->getIdProperties() as $idProperty) {
			if ($idProperty->isInterfaceable($private, $isSerialContext)) {
				$propertyName = $isSerialContext ? $idProperty->getSerializationName() : $idProperty->getName();
				$idValue = $interfacer->getValue($interfacedObject, $propertyName, $idProperty->isInterfacedAsNodeXml());
				$idValues[] = $idProperty->getModel()->importSimple($idValue, $interfacer, $isFirstLevel);
			} else {
				$idValues[] = null;
			}
		}
		return $this->encodeId($idValues);
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
	
	/**
	 * get comhon object instance according model and interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel = false) {
		$inheritance = $interfacer->getValue($interfacedObject, Interfacer::INHERITANCE_KEY);
		$model = is_null($inheritance) ? $this : $this->_getIneritedModel($inheritance);
		$id = $model->getIdFromInterfacedObject($interfacedObject, $interfacer, $isFirstLevel);
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, $localObjectCollection, $isFirstLevel);
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
		$isloaded = !$isForeign && (!$isFirstLevel || $interfacer->hasToFlagObjectAsLoaded());
		
		if (is_null($id) || !$this->hasIdProperties()) {
			$object = $this->getObjectInstance($isloaded);
		}
		else {
			$object = $localObjectCollection->getObject($id, $this->modelName);
			if ($this->isMain && is_null($object)) {
				$object = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
			}
			if (is_null($object)) {
				$object = $this->_buildObjectFromId($id, $isloaded, $interfacer->hasToFlagValuesAsUpdated());
				$localObjectCollection->addObject($object);
			}
			else {
				if ($this->isMain) {
					$localObjectCollection->addObject($object, false);
					if (!$localObjectCollection->hasObject($id, $this->modelName, false)) {
						$object->cast($this);
						$localObjectCollection->addObject($object, false);
					}
				}
				if ($isloaded || ($isFirstLevel && $interfacer->getMergeType() !== Interfacer::MERGE)) {
					$object->setIsLoaded($isloaded);
				}
			}
		}
		return $object;
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
	 * @return \Comhon\Object\ObjectUnique
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
			throw new ComhonException('Argument 1 ('.$type.') imcompatible with argument 2 ('.get_class($interfacer).')');
		}
		try {
			return $this->_importRoot($interfacedObject, $interfacer, new ObjectCollection());
		}
		catch (ComhonException $e) {
			throw new ImportException($e);
		}
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
	protected function _importRoot($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection) {
		$this->load();
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getNodeClasses()));
			}
		}
		
		switch ($interfacer->getMergeType()) {
			case Interfacer::MERGE:
				$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, true);
				$this->_fillObject($object, $interfacedObject, $interfacer, $this->_loadLocalObjectCollection($object), true);
				break;
			case Interfacer::OVERWRITE:
				$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, true);
				$object->reset();
				$this->_fillObject($object, $interfacedObject, $interfacer, new ObjectCollection(), true);
				break;
			case Interfacer::NO_MERGE:
				if ($this->isMain) {
					$existingObject = MainObjectCollection::getInstance()->getObject($this->getIdFromInterfacedObject($interfacedObject, $interfacer, true), $this->modelName);
					if (!is_null($existingObject)) {
						MainObjectCollection::getInstance()->removeObject($existingObject);
					}
				}
				$object = $this->_import($interfacedObject, $interfacer, new ObjectCollection(), true);
				
				if (!is_null($existingObject)) {
					MainObjectCollection::getInstance()->removeObject($object);
					MainObjectCollection::getInstance()->addObject($existingObject);
				}
				break;
			default:
				throw new ComhonException('undefined merge type '.$interfacer->getMergeType());
		}
		return $object;
	}
	
	/**
	 * import interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique|null
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getNodeClasses()));
			}
		}
		$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $localObjectCollection, $isFirstLevel);
		$this->_fillObject($object, $interfacedObject, $interfacer, $localObjectCollection, $isFirstLevel);
		return $object;
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
		$this->load();
		$this->verifValue($object);
		
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
				throw new ComhonException('Argument 1 ('.$type.') imcompatible with argument 2 ('.get_class($interfacer).')');
			}
		}
		
		try {
			$this->_verifIdBeforeFillObject($object, $this->getIdFromInterfacedObject($interfacedObject, $interfacer, true), $interfacer->hasToFlagValuesAsUpdated());
			
			if ($this->isMain) {
				MainObjectCollection::getInstance()->addObject($object, false);
			}
			$this->_fillObject($object, $interfacedObject, $interfacer, $this->_loadLocalObjectCollection($object), true);
			
			if ($interfacer->hasToFlagObjectAsLoaded()) {
				$object->setIsLoaded(true);
			}
		}
		catch (ComhonException $e) {
			throw new ImportException($e);
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
			throw new UnexpectedModelException($this, $object->getModel());
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
				throw new ComhonException("id must be the same as imported value id : {$object->getId()} !== $messageId");
			}
		} else if ($objectId === 0) {
			if ($id !== 0 && $id !== '0') {
				$messageId = is_null($id) ? 'null' : $id;
				throw new ComhonException("id must be the same as imported value id : {$object->getId()} !== $messageId");
			}
		}
		else if ($object->getId() != $id) {
			$messageId = is_null($id) ? 'null' : $id;
			throw new ComhonException("id must be the same as imported value id : {$object->getId()} !== $messageId");
		}
		
		if ($this->isMain) {
			$storedObject = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
			if (!is_null($storedObject) && $storedObject!== $object) {
				throw new ComhonException("A different instance object with same id '$id' already exists in MainObjectCollection.\n"
						.'If you want to build a new instance with this id, you must go through Model and specify merge type as '.Interfacer::NO_MERGE.' (no merge)');
			}
		}
	}
	
	/**
	 * fill comhon object with values from interfaced object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 */
	protected function _fillObject(ObjectUnique $object, $interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		$model = $object->getModel();
		if ($model !== $this && !$model->isInheritedFrom($this)) {
			throw new UnexpectedModelException($this, $model);
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_unFlattenValues($interfacedObject, $object, $interfacer);
		}
		
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$flagAsUpdated     = $interfacer->hasToFlagValuesAsUpdated();
		$properties        = $model->_getContextProperties($private);
		
		foreach ($properties as $propertyName => $property) {
			try {
				if ($property->isInterfaceable($private, $isSerialContext)) {
					$interfacedPropertyName = $isSerialContext ? $property->getSerializationName() : $propertyName;
					if ($interfacer->hasValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml())) {
						$value = $interfacer->getValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml());
						$value = $property->getModel()->_import($value, $interfacer, $localObjectCollection, $property->getModel()->_isNextLevelFirstLevel($isFirstLevel));
						$object->setValue($propertyName, $value, $flagAsUpdated);
					}
				}
			} catch (ComhonException $e) {
				throw new ImportException($e, $propertyName);
			}
		}
		if ($isSerialContext) {
			foreach ($model->multipleForeignProperties as $propertyName => $multipleForeignProperty) {
				try {
					$id = [];
					$allNull = true;
					foreach ($multipleForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
						if ($interfacer->hasValue($interfacedObject, $serializationName)) {
							$idPart = $interfacer->getValue($interfacedObject, $serializationName);
							$idPart = $idProperty->getModel()->importSimple($idPart, $interfacer, $isFirstLevel);
							if (!is_null($idPart)) {
								$allNull = false;
							}
							$id[] = $idPart;
						}
					}
					if (count($id) !== 0 && count($id) !== count($multipleForeignProperty->getMultipleIdProperties())) {
						throw new ComhonException('not complete multiple id foreign value');
					}
					if (!$allNull) {
						$value = $multipleForeignProperty->getModel()->_import(json_encode($id), $interfacer, $localObjectCollection, false);
						$object->setValue($propertyName, $value, $flagAsUpdated);
					}
				}
				catch (ComhonException $e) {
					throw new ImportException($e, $propertyName);
				}
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
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _importId($interfacedId, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		if ($interfacer->isNullValue($interfacedId)) {
			return null;
		}
		if ($interfacer->isComplexInterfacedId($interfacedId)) {
			if (!$interfacer->hasValue($interfacedId, Interfacer::COMPLEX_ID_KEY) || !$interfacer->hasValue($interfacedId, Interfacer::INHERITANCE_KEY)) {
				throw new ComhonException('object id must have property \''.Interfacer::COMPLEX_ID_KEY.'\' and \''.Interfacer::INHERITANCE_KEY.'\'');
			}
			$id = $interfacer->getValue($interfacedId, Interfacer::COMPLEX_ID_KEY);
			$inheritance = $interfacer->getValue($interfacedId, Interfacer::INHERITANCE_KEY);
			$model = $this->_getIneritedModel($inheritance);
		}
		else {
			$id = $interfacedId;
			$model = $this;
			
			if ($model->hasUniqueIdProperty()) {
				$id = $model->getUniqueIdProperty()->getModel()->importSimple($id, $interfacer, $isFirstLevel);
			}
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			/** @var SimpleModel $model */
			if ($model->hasUniqueIdProperty()) {
				$id = $model->getUniqueIdProperty()->getModel()->importSimple($id, $interfacer, $isFirstLevel);
			} else if (!is_string($id)) {
				$id = $interfacer->castValueToString($id);
			}
		}
		if (is_null($id)) {
			return null;
		}
		if (is_object($id) || is_array($id) || $id === '') {
			$id = is_object($id) || is_array($id) ? json_encode($id) : $id;
			throw new ComhonException("malformed id '$id' for model '{$this->modelName}'");
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
			throw new ComhonException("cannot export id of foreign property with model '{$this->modelName}' because object doesn't have complete id");
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
			throw new UnexpectedModelException($this, $object->getModel());
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
			$Obj = $this->getObjectInstance();
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		return true;
	}
	
}
