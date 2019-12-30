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
use Comhon\Object\AbstractComhonObject;
use Comhon\Object\ComhonObject;
use Comhon\Object\ComhonArray;
use Comhon\Exception\Model\UndefinedPropertyException;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Model\UnexpectedModelException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Exception\Model\CastComhonObjectException;
use Comhon\Serialization\Serialization;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Exception\Model\NotDefinedModelException;
use Comhon\Exception\Interfacer\DuplicatedIdException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Exception\Interfacer\NotReferencedValueException;
use Comhon\Visitor\ObjectFinder;
use Comhon\Exception\Interfacer\InterfaceException;
use Comhon\Exception\Interfacer\ContextIdException;
use Comhon\Exception\Interfacer\ObjectLoopException;
use Comhon\Exception\Value\MissingIdForeignValueException;
use Comhon\Exception\Interfacer\IncompatibleValueException;
use Comhon\Exception\Model\NoIdPropertyException;

class Model extends ModelComplex implements ModelUnique, ModelComhonObject {

	/** @var boolean */
	protected $isLoading = false;
	
	/** 
	 * list of parent models (current model extends these models).
	 * Comhon inheriance manage multiple inheritance so it may contain several models.
	 * first parent model (at index 0) is called main parent model. 
	 * current model may inherit serialization only from main parent model.
	 * 
	 * @var Model[] 
	 */
	private $parents = [];
	
	private $sharedIdModel;
	
	/** @var string */
	private $objectClass = ComhonObject::class;
	
	/** @var boolean */
	private $isExtended = false;
	
	/** @var boolean */
	private $isMain = false;
	
	/** @var boolean */
	private $isAbstract = true;
	
	/** @var Serialization */
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
	
	/** @var \Comhon\Model\Property\Property[] */
	private $requiredProperties = [];
	
	/** @var Property */
	private $uniqueIdProperty;
	
	/** @var boolean */
	private $hasPrivateIdProperty = false;
	
	private $manifestParser;
	
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
		if ($this->isLoaded || $this->isLoading) {
			return;
		}
		try {
			$this->isLoading = true;
			if (is_null($this->manifestParser)) {
				ModelManager::getInstance()->addManifestParser($this);
				if (is_null($this->manifestParser)) {
					throw new NotDefinedModelException($this->getName());
				}
			}
			$result = ModelManager::getInstance()->getProperties($this, $this->manifestParser);
			$this->isMain = $result[ModelManager::IS_MAIN_MODEL];
			$this->parents = $result[ModelManager::PARENT_MODELS];
			$this->sharedIdModel = $result[ModelManager::SHARED_ID_MODEL];
			$this->_setProperties($result[ModelManager::PROPERTIES]);
			$this->serialization = $result[ModelManager::SERIALIZATION];
			$this->isAbstract = $result[ModelManager::IS_ABSTRACT];
			$this->_verifyIdSerialization();
			
			if (!is_null($result[ModelManager::OBJECT_CLASS]) && ($this->objectClass !== $result[ModelManager::OBJECT_CLASS])) {
				$this->objectClass = $result[ModelManager::OBJECT_CLASS];
				$this->isExtended = true;
			}
			$this->_init();
			$this->isLoaded  = true;
			$this->isLoading = false;
			$this->manifestParser = null;
			
		} catch (\Exception $e) {
			// reinitialize attributes if any exception
			$this->isLoading = false;
			$this->parents = null;
			$this->objectClass = ComhonObject::class;
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
			$this->manifestParser = null;
			
			throw $e;
		}
	}
	
	/**
	 * set manifest parser to populate model attributes.
	 * should be called only during model loading.
	 * 
	 * @param \Comhon\Manifest\Parser\ManifestParser $manifestParser
	 * @throws ComhonException
	 */
	public function setManifestParser(ManifestParser $manifestParser) {
		if (!is_null($this->manifestParser)) {
			throw new ComhonException('error during model \''.$this->modelName.'\' loading');
		}
		$this->manifestParser = $manifestParser;
	}
	
	/**
	 * verify if model has a manifest parser set.
	 * should be called only during model loading.
	 *
	 * @return boolean
	 */
	public function hasManifestParser() {
		return !is_null($this->manifestParser);
	}
	
	/**
	 * set differents properties
	 *
	 * @param \Comhon\Model\Property\Property[] $properties
	 */
	protected function _setProperties($properties) {
		$publicIdProperties = [];
		
		foreach ($properties as $property) {
			if ($property->isId()) {
				$this->idProperties[$property->getName()] = $property;
				if (!$property->isPrivate()) {
					$publicIdProperties[$property->getName()] = $property;
				}
			}
			if ($property->hasDefaultValue()) {
				$this->propertiesWithDefaultValues[$property->getName()] = $property;
			} else if ($property->isAggregation()) {
				$this->aggregations[$property->getName()] = $property;
			} else if ($property->hasMultipleSerializationNames()) {
				$this->multipleForeignProperties[$property->getName()] = $property;
			}
			if ($property->isSerializable()) {
				$this->serializableProperties[$property->getName()] = $property;
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
			if ($property->isRequired()) {
				$this->requiredProperties[$property->getName()] = $property;
			}
			$this->properties[$property->getName()] = $property;
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
	 * verify if ids are compatible with parent that share id
	 *
	 * @param Model $model
	 * @param Model $sharedIdModel
	 * @throws ComhonException
	 */
	private function _verifyIdSerialization() {
		if (is_null($this->sharedIdModel)) {
			return;
		}
		if (count($this->getIdProperties()) != count($this->sharedIdModel->getIdProperties())) {
			throw new ComhonException("model {$this->getName()} share id with model {$this->sharedIdModel->getName()} so they must have same id(s)");
		}
		foreach ($this->sharedIdModel->getIdProperties() as $propertyName => $property) {
			if (!$this->hasIdProperty($propertyName) || !$property->isEqual($this->getIdProperty($propertyName))) {
				throw new ComhonException("model {$this->getName()} share id with model {$this->sharedIdModel->getName()} so they must have same id(s)");
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
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	public function getObjectInstance($isloaded = true) {
		if ($this->isExtended) {
			$object = new $this->objectClass($isloaded);

			if ($object->getModel() !== $this) {
				throw new UnexpectedModelException($this, $object->getModel());
			}
			return $object;
		} else {
			return new ComhonObject($this, $isloaded);
		}
		
	}
	
	/**
	 * verify if model is abstract.
	 * object with abstract model may be instanciated instanciated but cannot be set as loaded and cannot be interfaced
	 *
	 * @return boolean
	 */
	public function isAbstract() {
		return $this->isAbstract;
	}
	
	/**
	 * get model that share id with current model.
	 * if a model is return it is inevitably a parent of current model. 
	 * it may be the direct parent or the parent of parent, etc...
	 *
	 * @return Model|null null if there is no parent model that share id with current model.
	 */
	public function getSharedIdModel() {
		return $this->sharedIdModel;
	}
	
	/**
	 * get parent models. Comhon inheriance manage multiple inheritance so it may return several models.
	 * 
	 * @return Model[] if current model doesn't extend from any models, an empty array is returned.
	 */
	public function getParents() {
		return $this->parents;
	}
	
	/**
	 * get parent model at specified index (default 0) 
	 *
	 * @return Model|null null if parent model at specified index doesn't exist
	 */
	public function getParent($index = 0) {
		return isset($this->parents[$index]) ? $this->parents[$index] : null;
	}
	
	/**
	 * get first shared id parent model that match with all specified parameters.
	 * if a parameter is null, it is not taken in account in match.
	 *
	 * @param bool $sameSerializationSettings
	 * @param bool $isSerializable
	 * @return Model|null null if no parent model matches
	 */
	public function getFirstSharedIdParentMatch($sameSerializationSettings = null, $isSerializable = null) {
		return $this->_getSharedIdParentMatch($sameSerializationSettings, $isSerializable, true);
	}
	
	/**
	 * get last shared id parent model that match with all specified parameters.
	 * if a parameter is null, it is not taken in account in match.
	 *
	 * @param bool $sameSerializationSettings
	 * @param bool $isSerializable
	 * @return Model|null null if no parent model matches
	 */
	public function getLastSharedIdParentMatch($sameSerializationSettings = null, $isSerializable = null) {
		return $this->_getSharedIdParentMatch($sameSerializationSettings, $isSerializable, false);
	}
	
	/**
	 * get shared id model that match with all specified parameters.
	 * if a parameter is null, it is not taken in account in match.
	 * 
	 * @param bool $sameSerializationSettings
	 * @param bool $isSerializable
	 * @param bool $first if true stop at first parent match otherwise coninue to last parent match
	 * @return Model|null null if no parent model matches
	 */
	private function _getSharedIdParentMatch($sameSerializationSettings, $isSerializable, $first) {
		$model = $this;
		$parentMatch = null;
		$serializationSettings = $this->getSerializationSettings();
		$shareIdModel = ObjectCollection::getModelKey($this);
		while (!is_null($model->getParent())) {
			$model = $model->getParent();
			$parentSerialization = $model->getSerialization();
			
			if (ObjectCollection::getModelKey($model) !== $shareIdModel) {
				continue;
			}
			if (!is_null($isSerializable)) {
				if ((!is_null($parentSerialization) && $parentSerialization->isSerializationAllowed()) !== $isSerializable) {
					continue;
				}
			}
			if (!is_null($sameSerializationSettings)) {
				if (($model->getSerializationSettings() === $serializationSettings) !== $sameSerializationSettings) {
					continue;
				}
			}
			$parentMatch = $model;
			if ($first) {
				break;
			}
		}
		return $parentMatch;
	}
	
	/**
	 * verify if model extends from at least another one
	 * 
	 * @return boolean
	 */
	public function hasParent() {
		return !empty($this->parents);
	}
	
	/**
	 * verify if current model inherit from specified model
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return boolean
	 */
	public function isInheritedFrom(Model $model) {
		$isInherited = false;
		foreach ($this->getParents() as $parent) {
			$isInherited = $model === $parent;
			if (!$isInherited) {
				$isInherited = $parent->isInheritedFrom($model);
			}
			if ($isInherited) {
				break;
			}
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
	 * verify if model is loading or not
	 *
	 * @return boolean
	 */
	public function isLoading() {
		return $this->isLoading;
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
	 * @throws \Comhon\Exception\Model\UndefinedPropertyException
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
	 * @throws \Comhon\Exception\Model\UndefinedPropertyException
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
	 * @param string $serializationType ("Comhon\SqlTable", "Comhon\File\JsonFile"...)
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getForeignSerializableProperties($serializationType) {
		$properties = [];
		foreach ($this->properties as $property) {
			if (($property instanceof ForeignProperty) && $property->hasSerialization($serializationType)) {
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
	 * get required proprties
	 *
	 * @return \Comhon\Model\Property\Property[]:
	 */
	public function getRequiredProperties() {
		return $this->requiredProperties;
	}
	
	/**
	 * verify if model is a main model
	 * if true that means comhon object with current model might be stored in MainObjectCollection
	 *
	 * @return boolean
	 */
	public function isMain() {
		return $this->isMain;
	}
	
	/**
	 * get serialization linked to model
	 * 
	 * @return \Comhon\Serialization\Serialization|null
	 */
	public function getSerialization() {
		return $this->serialization;
	}
	
	/**
	 * verify if model has serialization
	 * 
	 * @param string $serializationType
	 * @return boolean
	 */
	public function hasSerialization($serializationType = null) {
		return !is_null($this->serialization) && (is_null($serializationType) || $this->serialization->getSettings()->getModel()->getName() === $serializationType);
	}
	
	/**
	 * get serialization settings (if model has linked serialzation)
	 * 
	 * @return \Comhon\Object\UniqueObject|null null if no serialization settings
	 */
	public function getSerializationSettings() {
		return is_null($this->serialization) ? null : $this->serialization->getSettings();
	}
	
	/**
	 * verify if model has linked sql serialization
	 *
	 * @return boolean
	 */
	public function hasSqlTableSerialization() {
		return !is_null($this->serialization) && ($this->serialization->getSerializationUnit() instanceof SqlTable);
	}
	
	/**
	 * get linked sql serialization settings (if model has linked sql serialzation)
	 *
	 * @return \Comhon\Object\UniqueObject|null null if no sql serialization
	 */
	public function getSqlTableSettings() {
		return !is_null($this->serialization) && ($this->serialization->getSerializationUnit() instanceof SqlTable) ? $this->serialization->getSettings() : null;
	}
	
	/**
	 * get linked sql serialization (if model has linked sql serialzation)
	 *
	 * @return \Comhon\Serialization\SqlTable|null null if no sql serialization
	 */
	public function getSqlTableUnit() {
		return !is_null($this->serialization) && ($this->serialization->getSerializationUnit()instanceof SqlTable) ? $this->serialization->getSerializationUnit(): null;
	}
	
	/**
	 * encode multiple ids in json format
	 * 
	 * @param array $idValues 
	 * @return string
	 */
	public static function encodeId($idValues) {
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
	 * load comhon object
	 *
	 * @param string|integer $id
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject|null null if load is unsuccessfull
	 */
	public function loadObject($id, $propertiesFilter = null, $forceLoad = false) {
		if (is_null($this->getSerialization())) {
			throw new ComhonException("model {$this->getName()} doesn't have serialization");
		}
		$this->load();
		if (!$this->hasIdProperties()) {
			throw new NoIdPropertyException($this);
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
			}
			throw $e;
		} catch (ImportException $e) {
			if ($newObject && ($e->getOriginalException() instanceof CastComhonObjectException)) {
				$mainObject->reset();
			}
			throw $e;
		}
	}
	
	/**
	 * load instancied comhon object with serialized object
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject|null null if load is unsuccessfull
	 */
	public function loadAndFillObject(UniqueObject $object, $propertiesFilter = null, $forceLoad = false) {
		$success = false;
		$this->load();
		if (is_null($serialization = $this->getSerialization())) {
			throw new ComhonException("model {$this->getName()} doesn't have serialization");
		}
		if (!$object->isLoaded() || $forceLoad) {
			$success = $serialization->getSerializationUnit()->loadObject($object, $propertiesFilter);
		}
		return $success;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_exportRoot()
	 */
	protected function _exportRoot(AbstractComhonObject $object, $nodeName, Interfacer $interfacer) {
		try {
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
			$node = $this->_export($object, $nodeName, $interfacer, true, $objectCollectionInterfacer);
			if ($interfacer->hasToVerifyReferences()) {
				$this->_verifyReferences($object, $objectCollectionInterfacer);
			}
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
		return $node;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_export()
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		/** @var \Comhon\Object\UniqueObject $object */
		if (is_null($object)) {
			return null;
		}
		$object->validate();
		
		$node              = $interfacer->createNode($nodeName);
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$onlyUpdatedValues = $isFirstLevel && $interfacer->hasToExportOnlyUpdatedValues();
		$propertiesFilter  = $interfacer->getPropertiesFilter($object->getModel()->getName());
		
		if (array_key_exists(spl_object_hash($object), self::$instanceObjectHash)) {
			if (self::$instanceObjectHash[spl_object_hash($object)] > 0) {
				throw new ObjectLoopException();
			}
		} else {
			self::$instanceObjectHash[spl_object_hash($object)] = 0;
		}
		self::$instanceObjectHash[spl_object_hash($object)]++;
		
		if ($object->getModel()->hasIdProperties()) {
			if ($objectCollectionInterfacer->hasNewObject($object->getId(), $object->getModel()->getName())) {
				throw new DuplicatedIdException($object->getId());
			}
			$objectCollectionInterfacer->addObject($object, false);
		}
		
		$properties = $object->getModel()->_getContextProperties($private);
		foreach ($object->getValues() as $propertyName => $value) {
			try {
				if (array_key_exists($propertyName, $properties)) {
					$property = $properties[$propertyName];
					
					if ($property->isExportable($private, $isSerialContext, $value)) {
						if ((!$onlyUpdatedValues || $property->isId() || $object->isUpdatedValue($propertyName))
							&& (is_null($propertiesFilter) || array_key_exists($propertyName, $propertiesFilter))) {
							$propertyName  = $isSerialContext ? $property->getSerializationName() : $propertyName;
							$exportedValue = $property->getModel()->_export($value, $propertyName, $interfacer, false, $objectCollectionInterfacer);
							$interfacer->setValue($node, $exportedValue, $propertyName, $property->isInterfacedAsNodeXml());
						}
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
							throw new MissingIdForeignValueException();
						}
						foreach ($multipleForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
							if (!$onlyUpdatedValues || $foreignObject->isUpdatedValue($idProperty->getName())) {
								$idValue = $foreignObject->getValue($idProperty->getName());
								$idValue = $idProperty->getModel()->_export($idValue, $serializationName, $interfacer, false, $objectCollectionInterfacer);
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
		if ($isFirstLevel && $isSerialContext) {
			if ($object->getModel()->getSerialization() && $object->getModel()->getSerialization()->getInheritanceKey()) {
				$interfacer->setValue($node, $object->getModel()->getName(), $object->getModel()->getSerialization()->getInheritanceKey());
			}
		} elseif ($object->getModel() !== $this) {
			$interfacer->setValue($node, $object->getModel()->getName(), Interfacer::INHERITANCE_KEY);
		}
		self::$instanceObjectHash[spl_object_hash($object)]--;
		return $node;
	}
	
	/**
	 * flatten complex values of specified node
	 * 
	 * @param mixed $node
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _flattenValues(&$node, UniqueObject $object, Interfacer $interfacer) {
		foreach ($object->getModel()->getComplexProperties() as $propertyName => $complexProperty) {
			$interfacedPropertyName = $interfacer->isSerialContext() ? $complexProperty->getSerializationName() : $propertyName;
			
			if (!$complexProperty->isForeign() || ($object->getValue($propertyName) instanceof ComhonArray)) {
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
	protected function _exportId(AbstractComhonObject $object, $nodeName, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$model = $object->getModel();
		if (!$model->hasIdProperties()) {
			throw new ComhonException("cannot export id, actual model '{$model->getName()}' doesn't have id");
		}
		if (!$interfacer->isPrivateContext() && $model->hasPrivateIdProperty()) {
			throw new ContextIdException();
		}
		$objectCollectionInterfacer->addObject($object, true);
		// for object model different than current model but that share id with current model 
		// we may export only id whitout inheritance
		// but for main model we keep inheritance because it can be a usefull information
		if ($model === $this || (!$model->isMain() && ObjectCollection::getModelKey($this) === ObjectCollection::getModelKey($model))) {
			$exportedId = self::_toInterfacedId($object, $interfacer);
		} else {
			if (!$model->isInheritedFrom($this)) {
				throw new UnexpectedModelException($this, $model);
			}
			$exportedId = $interfacer->createNode($nodeName);
			$interfacer->setValue($exportedId, self::_toInterfacedId($object, $interfacer), Interfacer::COMPLEX_ID_KEY);
			$interfacer->setValue($exportedId, $model->getName(), Interfacer::INHERITANCE_KEY);
		}
		
		return $exportedId;
	}
	
	/**
	 * get inherited model name from interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param bool $isFirstLevel
	 * @return string|null
	 */
	protected function _getInheritedModelName($interfacedObject, Interfacer $interfacer, $isFirstLevel) {
		if ($isFirstLevel && $interfacer->isSerialContext()) {
			$inheritance = $this->getSerialization() && $this->getSerialization()->getInheritanceKey()
				? $interfacer->getValue($interfacedObject, $this->getSerialization()->getInheritanceKey())
				: null;
		} else {
			$inheritance = $interfacer->getValue($interfacedObject, Interfacer::INHERITANCE_KEY);
		}
		return $inheritance;
	}
	
	/**
	 * get inherited model
	 *
	 * @param string $inheritanceModelName
	 * @return Model;
	 */
	protected function _getInheritedModel($inheritanceModelName) {
		$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName);
		if ($model !== $this && !$model->isInheritedFrom($this)) {
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
		return self::encodeId($idValues);
	}
	
	/**
	 * get comhon object instance according model and interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$inheritance = $this->_getInheritedModelName($interfacedObject, $interfacer, $isFirstLevel);
		$model = is_null($inheritance) ? $this : $this->_getInheritedModel($inheritance);
		$id = $model->getIdFromInterfacedObject($interfacedObject, $interfacer, $isFirstLevel);
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, $isFirstLevel, false, $objectCollectionInterfacer);
	}
	
	/**
	 * get or create an instance of AbstractComhonObject
	 *
	 * @param integer|string $id
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param boolean $isForeign
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Exception
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	protected function _getOrCreateObjectInstance($id, Interfacer $interfacer, $isFirstLevel, $isForeign, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		if (is_null($id) || !$this->hasIdProperties()) {
			$object = $this->getObjectInstance(false);
		}
		else {
			$key = ObjectCollection::getModelKey($this)->getName();
			$object = $objectCollectionInterfacer->getObject($id, $key);
			if (!is_null($object) && !$isForeign && $objectCollectionInterfacer->hasNewObject($id, $key)) {
				throw new DuplicatedIdException($id);
			}
			if ($this->isMain && is_null($object)) {
				$object = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
			}
			if (is_null($object)) {
				$object = $this->_buildObjectFromId($id, false, $interfacer->hasToFlagValuesAsUpdated());
				$objectCollectionInterfacer->addObject($object, $isForeign);
			}
			else {
				if ($this->isInheritedFrom($object->getModel()) && ObjectCollection::getModelKey($this) === ObjectCollection::getModelKey($object->getModel())) {
					$object->cast($this);
				}
				if (!$isForeign && !$isFirstLevel) {
					$object->reset(false);
				}
				$objectCollectionInterfacer->addObject($object, $isForeign);
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
	 * @return \Comhon\Object\UniqueObject
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			throw new IncompatibleValueException($interfacedObject, $interfacer);
		}
		try {
			return $this->_importRoot($interfacedObject, $interfacer);
		}
		catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	/**
	 * import interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $startObjColInterfacer
	 * @param \Comhon\Object\UniqueObject $startObject
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importRoot($interfacedObject, Interfacer $interfacer, ObjectCollectionInterfacer $startObjColInterfacer = null, UniqueObject $startObject = null) {
		$this->load();
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getNodeClasses()));
			}
		}
		if (!is_null($startObjColInterfacer) && !is_null($startObject)) {
			throw new ComhonException('$startObjCol and $startObject cannot be set in same time');
		}
		if (is_null($startObjColInterfacer)) {
			$startObjColInterfacer = new ObjectCollectionInterfacer();
		}
		
		switch ($interfacer->getMergeType()) {
			case Interfacer::MERGE:
				$object = is_null($startObject) 
					? $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, true, $startObjColInterfacer)
					: $startObject;
				$objectCollectionInterfacer = new ObjectCollectionInterfacer($object);
				$objectCollectionInterfacer->addObject($object, false);
				$this->_fillObject($object, $interfacedObject, $interfacer, true, $objectCollectionInterfacer);
				
				if ($interfacer->hasToVerifyReferences()) {
					// if object already exists (for exemple during fill object) some property might be not visited
					// because they are not in object to import. so we have to visit filled object to collect all objects present
					// and we will be able to compare foeriegn and not foreign values
					if ($startObject || $startObjColInterfacer->hasStartObject($object->getId(), $object->getModel()->getName())) {
						$objectCollectionInterfacer->replaceNewObjectCollection(ObjectCollection::build($object, false));
					}
				}
				break;
			case Interfacer::OVERWRITE:
				$object = is_null($startObject)
					? $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, true, $startObjColInterfacer)
					: $startObject;
				$objectCollectionInterfacer = new ObjectCollectionInterfacer();
				$objectCollectionInterfacer->addObject($object, false);
				$isLoaded = $object->isLoaded();
				$object->reset();
				$this->_fillObject($object, $interfacedObject, $interfacer, true, $objectCollectionInterfacer);
				$object->setIsLoaded($isLoaded);
				break;
			default:
				throw new ComhonException('undefined merge type '.$interfacer->getMergeType());
		}
		if ($interfacer->hasToFlagObjectAsLoaded()) {
			$object->setIsLoaded(true);
		}
		if ($interfacer->hasToVerifyReferences()) {
			$this->_verifyReferences($object, $objectCollectionInterfacer);
		}
		
		return $object;
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Comhon\Exception\ComhonException
	 */
	private function _verifyReferences(UniqueObject $object, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$objects = $objectCollectionInterfacer->getNotReferencedObjects();
		if (!empty($objects)) {
			$objectFinder = new ObjectFinder();
			foreach ($objects as $obj) {
				$statck = $objectFinder->execute(
					$object,
					[
						ObjectFinder::ID => $obj->getId(),
						ObjectFinder::MODEL => $obj->getModel(),
						ObjectFinder::SEARCH_FOREIGN => true
					]
				);
				if (is_null($statck)) {
					throw new ComhonException('value should not be null');
				}
				// for the moment InterfaceException manage only one error
				// so we throw exception at the first loop
				throw InterfaceException::getInstanceWithProperties(
					new NotReferencedValueException($obj),
					array_reverse($statck)
				);
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_import()
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
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
		$object = $this->_getOrCreateObjectInstanceFromInterfacedObject($interfacedObject, $interfacer, $isFirstLevel, $objectCollectionInterfacer);
		$this->_fillObject($object, $interfacedObject, $interfacer, $isFirstLevel, $objectCollectionInterfacer);
		return $object;
	}
	
	/**
	 * fill comhon object with values from interfaced object
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	public function fillObject(AbstractComhonObject $object, $interfacedObject, Interfacer $interfacer) {
		$this->load();
		$this->verifValue($object);
		
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		
		try {
			$inheritance = $this->_getInheritedModelName($interfacedObject, $interfacer, true);
			if (!is_null($inheritance)) {
				$object->cast(ModelManager::getInstance()->getInstanceModel($inheritance));
			}
			$model = $object->getModel();
			$model->_verifIdBeforeFillObject($object, $model->getIdFromInterfacedObject($interfacedObject, $interfacer, true), $interfacer->hasToFlagValuesAsUpdated());
			
			$startObject = null;
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
			
			if (!$objectCollectionInterfacer->addStartObject($object, false)) {
				$objectCollectionInterfacer = null;
				$startObject = $object;
			}
			$model->_importRoot($interfacedObject, $interfacer, $objectCollectionInterfacer, $startObject);
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
	 * @param \Comhon\Object\UniqueObject $object
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 */
	private function _verifIdBeforeFillObject(UniqueObject $object, $id, $flagAsUpdated) {
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
		if ($object->getId() !== $id) {
			$messageId = is_null($id) ? 'null' : $id;
			throw new ComhonException("id must be the same as imported value id : {$object->getId()} !== $messageId");
		}
	}
	
	/**
	 * fill comhon object with values from interfaced object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Exception
	 */
	protected function _fillObject(UniqueObject $object, $interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
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
						$interfacedValue = $interfacer->getValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml());
						$value =  $interfacer->isNullValue($interfacedValue) ? null
							: $property->getModel()->_import($interfacedValue, $interfacer, $property->getModel()->_isNextLevelFirstLevel($isFirstLevel), $objectCollectionInterfacer);
						
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
						$value = $multipleForeignProperty->getModel()->_import(json_encode($id), $interfacer, false, $objectCollectionInterfacer);
						$object->setValue($propertyName, $value, $flagAsUpdated);
					}
				}
				catch (ComhonException $e) {
					throw new ImportException($e, $propertyName);
				}
			}
		}
		if (!$isFirstLevel) {
			$object->setIsLoaded(true);
		}
	}
	
	/**
	 * unflatten complex values from interfaced object
	 *
	 * @param mixed $node
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected function _unFlattenValues(&$node, UniqueObject $object, Interfacer $interfacer) {
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
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importId($interfacedId, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		if (!$this->hasIdProperties()) {
			throw new ComhonException("cannot import id, actual model '{$this->getUniqueModel()->getName()}' doesn't have id");
		}
		if (!$interfacer->isPrivateContext() && $this->hasPrivateIdProperty()) {
			throw new ContextIdException();
		}
		if ($interfacer->isNullValue($interfacedId)) {
			return null;
		}
		if ($interfacer->isComplexInterfacedId($interfacedId)) {
			if (!$interfacer->hasValue($interfacedId, Interfacer::COMPLEX_ID_KEY) || !$interfacer->hasValue($interfacedId, Interfacer::INHERITANCE_KEY)) {
				throw new ComhonException('object id must have property \''.Interfacer::COMPLEX_ID_KEY.'\' and \''.Interfacer::INHERITANCE_KEY.'\'');
			}
			$id = $interfacer->getValue($interfacedId, Interfacer::COMPLEX_ID_KEY);
			$inheritance = $interfacer->getValue($interfacedId, Interfacer::INHERITANCE_KEY);
			$model = $this->_getInheritedModel($inheritance);
		}
		else {
			$id = $interfacedId;
			$model = $this;
			
			if ($model->hasUniqueIdProperty()) {
				$id = $model->getUniqueIdProperty()->getModel()->importSimple($id, $interfacer, $isFirstLevel);
			}
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
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
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, false, true, $objectCollectionInterfacer);
	}
	
	/**
	 * build interface id from comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return integer|string
	 */
	private static function _toInterfacedId(UniqueObject $object, Interfacer $interfacer) {
		if (!$object->hasCompleteId()) {
			throw new MissingIdForeignValueException();
		}
		return $object->getId();
	}
	
	/**
	 * create comhon object and fill it with id
	 * 
	 * @param mixed $id
	 * @param boolean $isloaded
	 * @param boolean $flagAsUpdated
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _buildObjectFromId($id, $isloaded, $flagAsUpdated) {
		return $this->_fillObjectwithId($this->getObjectInstance($isloaded), $id, $flagAsUpdated);
	}
	
	/**
	 * fill comhon object with id
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _fillObjectwithId(UniqueObject $object, $id, $flagAsUpdated) {
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
		if (!($value instanceof UniqueObject) || ($value->getModel() !== $this && !$value->getModel()->isInheritedFrom($this))) {
			$Obj = $this->getObjectInstance(false);
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		return true;
	}
	
}
