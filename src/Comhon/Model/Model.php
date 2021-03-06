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
use Comhon\Serialization\Serialization;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Exception\Model\NotDefinedModelException;
use Comhon\Exception\Interfacer\DuplicatedIdException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Exception\Interfacer\ContextIdException;
use Comhon\Exception\Interfacer\ObjectLoopException;
use Comhon\Exception\Value\MissingIdForeignValueException;
use Comhon\Exception\Interfacer\IncompatibleValueException;
use Comhon\Exception\Model\NoIdPropertyException;
use Comhon\Exception\Value\InvalidCompositeIdException;
use Comhon\Exception\Interfacer\AbstractObjectExportException;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;

class Model extends ModelComplex implements ModelUnique, ModelComhonObject {

	/** @var boolean */
	private $isLoading = false;
	
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
	private $isAbstract = false;
	
	/** @var Serialization */
	private $serialization = null;
	
	/** @var boolean */
	private $isOptionsLoaded = false;
	
	/** @var \Comhon\Object\UniqueObject */
	private $options = null;
	
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
	
	/** @var \Comhon\Model\Property\Property[] */
	private $dependsProperties = [];
	
	/** @var string[][] */
	private $conflicts = [];
	
	/** @var Property */
	private $uniqueIdProperty;
	
	/** @var boolean */
	private $hasPrivateIdProperty = false;
	
	/** @var \Comhon\Manifest\Parser\ManifestParser */
	private $manifestParser;
	
	/** @var \Comhon\Model\Model[] */
	private $localModels;
	
	/** @var string[] array of local models names defined in manifest */
	private $localTypes = [];
	
	/**
	 * don't instanciate a model by yourself because it take time.
	 * to get a model instance use singleton ModelManager.
	 * 
	 * @param string $modelName
	 */
	public function __construct($modelName) {
		$this->modelName = $modelName;
		ModelManager::getInstance()->addInstanceModel($this);
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
			$cacheHandler = ModelManager::getInstance()->getCacheHandler();
			if (
				!is_null($cacheHandler)
				&& $cacheHandler->hasValue($cacheHandler->getModelKey($this->modelName))
			) {
				ModelManager::getInstance()->loadModelFromCache($this->modelName);
			} else {
				$this->_loadFromManfiest();
				if (!is_null($cacheHandler)) {
					// $this->isLoaded must be true before register model
					ModelManager::getInstance()->registerModelIntoCache($this);
				}
			}
			if (!$this->isLoaded) {
				throw new ComhonException('model should be flagged loaded');
			}
		} catch (\Exception $e) {
			// reinitialize attributes if any exception
			$this->isLoading = false;
			$this->parents = null;
			$this->objectClass = ComhonObject::class;
			$this->isMain = false;
			$this->isAbstract = false;
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
			$this->requiredProperties = [];
			$this->dependsProperties = [];
			$this->conflicts = [];
			$this->localTypes = [];
			$this->uniqueIdProperty = null;
			$this->hasPrivateIdProperty = false;
			$this->manifestParser = null;
			$this->sharedIdModel = null;
			$this->serialization = null;
			
			if (isset($this->localModels)) {
				foreach ($this->localModels as $localModel) {
					$localModel->manifestParser = null;
				}
			}
			$this->localModels = null;
			
			throw $e;
		}
	}
	
	/**
	 * overwite current model with given model.
	 * this function must be called only in caching context.
	 * 
	 * @param Model $model the cached model
	 */
	public function overwrite(Model $model) {
		if (!ModelManager::getInstance()->isCachingContext()) {
			throw new ComhonException('error, function overwrite may be called only in caching context');
		}
		if ($this->isLoaded) {
			throw new ComhonException('error, function overwrite may be called only on unloaded model');
		}
		if ($model->getName() !== $this->modelName) {
			throw new ComhonException(
				"error, try to overwrite model '{$this->modelName}' with '{$model->getName()}' (must have same name)"
			);
		}
		
		$this->parents = $model->parents;
		$this->sharedIdModel = $model->sharedIdModel;
		$this->objectClass = $model->objectClass;
		$this->isMain = $model->isMain;
		$this->isAbstract = $model->isAbstract;
		$this->isExtended = $model->isExtended;
		$this->serialization = $model->serialization;
		$this->properties   = $model->properties;
		$this->conflicts = $model->conflicts;
		$this->localTypes = $model->localTypes;
		
		$this->isLoaded = true;
		$this->isLoading = false;
	}
	
	/**
	 * restore model that have been unserialized from cache.
	 * this function must be called only in caching context.
	 */
	public function restore() {
		if (!ModelManager::getInstance()->isCachingContext()) {
			throw new ComhonException('error function overwrite may be called only in caching context');
		}
		$properties = [];
		foreach ($this->parents as $key => $parentName) {
			$this->parents[$key] = ModelManager::getInstance()->getInstanceModel($parentName);
			$properties = array_merge($properties, $this->parents[$key]->getProperties());
		}
		foreach ($this->getProperties() as $property) {
			$property->restore();
		}
		$properties = array_merge($properties, $this->getProperties());
		$this->properties = [];
		$this->_setProperties($properties);
		if (!is_null($this->sharedIdModel)) {
			$this->sharedIdModel = ModelManager::getInstance()->getInstanceModel($this->sharedIdModel);
		}
		if (!is_null($this->serialization)) {
			$this->serialization->restore();
		}
	}
	
	/**
	 * load model from manifest
	 * 
	 * @throws NotDefinedModelException
	 */
	private function _loadFromManfiest() {
		if (is_null($this->manifestParser)) {
			$this->localModels = ModelManager::getInstance()->addManifestParser($this);
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
		foreach ($result[ModelManager::CONFLICTS] as $properties) {
			foreach ($properties as $i => $propertyName) {
				if (!array_key_exists($propertyName, $this->conflicts)) {
					$this->conflicts[$propertyName] = [];
				}
				foreach ($properties as $j => $conflictPropertyName) {
					if ($j != $i) {
						$this->conflicts[$propertyName][] = $conflictPropertyName;
					}
				}
			}
		}
		$this->localModels = null;
		$this->manifestParser = null;
		$this->isLoaded  = true;
		$this->isLoading = false;
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
	 * set local types (model names defined in manifest local types).
	 *
	 * @param string[] $localTypes
	 */
	public function setLocalTypes(array $localTypes) {
		$this->localTypes = $localTypes;
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
			if ($property->hasDependencies()) {
				$this->dependsProperties[$property->getName()] = $property;
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
	 * register model (and nested models) in model manager if needed.
	 * (used when config object is unserialized from cache)
	 *
	 * @return bool
	 */
	public function register() {
		if (ModelManager::getInstance()->hasInstanceModel($this->modelName)) {
			return false;
		}
		ModelManager::getInstance()->addInstanceModel($this);
		foreach ($this->getProperties() as $property) {
			$property->registerModel();
		}
		if (!is_null($this->sharedIdModel)) {
			$this->sharedIdModel->register();
		}
		foreach ($this->parents as $parent) {
			$parent->register();
		}
		if (
			isset($this->parents[0])
			&& $this->parents[0]->getName() === 'Comhon\Root'
			&& $this->parents[0] !== ModelManager::getInstance()->getInstanceModel('Comhon\Root')
		) {
			$this->parents[0] = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		}
		if (!is_null($settings = $this->getSerializationSettings())) {
			$settings->getModel()->register();
		}
		return true;
	}
	
	/**
	 * 
	 * serialize model.
	 * this function must be called only in caching context.
	 * 
	 * @return string
	 */
	public function serialize() {
		if (!ModelManager::getInstance()->isCachingContext()) {
			throw new ComhonException('error function serialize may be called only in caching context');
		}
		if (!is_null($this->serialization)) {
			$this->serialization->serialize($this->getFirstSharedIdParentMatch(true));
		}
		if (!is_null($this->sharedIdModel)) {
			$this->sharedIdModel = $this->sharedIdModel->getName();
		}
		$parentProperties = [];
		$parents = [];
		foreach ($this->parents as $parent) {
			$parentProperties = array_merge($parentProperties, $parent->getProperties());
			$parents[] = $parent->getName();
		}
		
		$this->parents = $parents;
		$this->properties = array_diff_key($this->properties, $parentProperties);
		foreach ($this->properties as $property) {
			$property->serialize();
		}
		$this->idProperties = [];
		$this->aggregations = [];
		$this->publicProperties  = [];
		$this->serializableProperties = [];
		$this->propertiesWithDefaultValues = [];
		$this->multipleForeignProperties = [];
		$this->complexProperties = [];
		$this->dateTimeProperties = [];
		$this->requiredProperties = [];
		$this->dependsProperties = [];
		$this->uniqueIdProperty = null;
		$this->hasPrivateIdProperty = false;
		
		$serial = serialize($this);
		$this->restore();
		
		return $serial;
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::getObjectInstance()
	 * @return \Comhon\Object\UniqueObject
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
	 * get first shared id parent model found. search from direct parent to last parent.
	 *
	 * @param bool $sameSerializationSettings if provided and true, only look at parent model with same serialization,
	 *                                        otherwise serialization is ignored
	 * @return Model|null null if no parent model matches
	 */
	public function getFirstSharedIdParentMatch($sameSerializationSettings = false) {
		return $this->_getSharedIdParentMatch($sameSerializationSettings, true);
	}
	
	/**
	 * get last shared id parent model found. search from direct parent to last parent.
	 *
	 * @param bool $sameSerializationSettings if provided and true, only look at parent model with same serialization,
	 *                                        otherwise serialization is ignored
	 * @return Model|null null if no parent model matches
	 */
	public function getLastSharedIdParentMatch($sameSerializationSettings = null) {
		return $this->_getSharedIdParentMatch($sameSerializationSettings, false);
	}
	
	/**
	 * get first or last shared id parent model found. search from direct parent two last parent.
	 * 
	 * @param bool $sameSerializationSettings if provided and true, only look at parent model with same serialization,
	 *                                        otherwise serialization is ignored
	 * @param bool $first if true stop at first parent match otherwise coninue to last parent match
	 * @return Model|null null if no parent model matches
	 */
	private function _getSharedIdParentMatch($sameSerializationSettings, $first) {
		$model = $this;
		$parentMatch = null;
		$serializationSettings = $this->getSerializationSettings();
		$serializationUnitClass = $this->getSerialization() ? $this->getSerialization()->getSerializationUnitClass() : null;
		$shareIdModel = ObjectCollection::getModelKey($this);
		while (!is_null($model->getParent())) {
			$model = $model->getParent();
			$parentSerialization = $model->getSerialization();
			
			if (ObjectCollection::getModelKey($model) !== $shareIdModel) {
				continue;
			}
			if ($sameSerializationSettings === true) {
				$parentSerializationUnitClass = $parentSerialization ? $parentSerialization->getSerializationUnitClass() : null;
				if (
					$model->getSerializationSettings() !== $serializationSettings
					|| $parentSerializationUnitClass !== $serializationUnitClass
				) {
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
	 * get model names of local types that are defined in manifest
	 *
	 * @return string[]
	 */
	public function getLocalTypes() {
		return $this->localTypes;
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
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getPropertiesWithDefaultValues() {
		return $this->propertiesWithDefaultValues;
	}
	
	/**
	 * get aggregation proprties
	 *
	 * @return \Comhon\Model\Property\AggregationProperty[]
	 */
	public function getAggregationProperties() {
		return $this->aggregations;
	}
	
	/**
	 * get required properties
	 *
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getRequiredProperties() {
		return $this->requiredProperties;
	}
	
	/**
	 * verify if specified property has conflits with other properties.
	 * a property has conflict with other properties if property value MUST NOT be set when other properties values are set.
	 *
	 * @param string $propertyName name of property
	 * @return boolean
	 */
	public function hasPropertyConflicts($propertyName) {
		return isset($this->conflicts[$propertyName]);
	}
	
	/**
	 * get properties names that have conflicts with specified property.
	 * a property has conflict with other properties if property value MUST NOT be set when other properties values are set.
	 *
	 * @param string $propertyName name of property
	 * @return string[]
	 */
	public function getPropertyConflicts($propertyName) {
		return isset($this->conflicts[$propertyName]) ? $this->conflicts[$propertyName] : [];
	}
	
	/**
	 * get all conflicts.
	 * a property has conflict with other properties if property value MUST NOT be set when other properties values are set.
	 *
	 * @return string[]
	 */
	public function getConflicts() {
		return $this->conflicts;
	}
	
	/**
	 * get all properties that depends on other property(ies).
	 * a property depends on other properties if property value MAY be set only if other properties values are set.
	 *
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getDependsProperties() {
		return $this->dependsProperties;
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
	 * @return boolean
	 */
	public function hasSerialization() {
		return !is_null($this->serialization);
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
		return $this->hasSqlTableSerialization() ? $this->serialization->getSettings() : null;
	}
	
	/**
	 * get linked sql serialization (if model has linked sql serialzation)
	 *
	 * @return \Comhon\Serialization\SqlTable|null null if no sql serialization
	 */
	public function getSqlTableUnit() {
		return $this->hasSqlTableSerialization() ? $this->serialization->getSerializationUnit(): null;
	}
	
	/**
	 * load options
	 *
	 * @return \Comhon\Object\UniqueObject|null null if no options settings
	 */
	private function _loadOptions() {
		if (!$this->isOptionsLoaded) {
			$this->options = ModelManager::getInstance()->getInstanceModel('Comhon\Options')->loadObject($this->modelName);
			if (is_null($this->options) && !is_null($this->getParent())) {
				$this->options = $this->getParent()->getOptions();
			}
			$this->isOptionsLoaded = true;
		}
	}
	
	/**
	 * get options
	 *
	 * @return \Comhon\Object\UniqueObject|null null if no defined options
	 */
	public function getOptions() {
		if (!$this->isOptionsLoaded) {
			$this->_loadOptions();
		}
		return $this->options;
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
			throw new InvalidCompositeIdException($id);
		}
		return $decodedId;
	}
	
	/**
	 * verify if composite id has all id values not null and are not empty string.
	 * do not verify values types.
	 * 
	 * @param string $id
	 * @return boolean
	 */
	public function isCompleteId($id) {
		if ($this->hasUniqueIdProperty()) {
			return true;
		}
		$decodedId = $this->decodeId($id);
		for ($i = 0; $i < count($decodedId); $i++) {
			if (is_null($decodedId[$i]) || $decodedId[$i] === '') {
				return false;
			}
		}
		return true;
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
		if (is_null($id)) {
			return null;
		}
		if (is_null($this->getSerialization())) {
			throw new ComhonException("model {$this->getName()} doesn't have serialization");
		}
		if (!$this->hasIdProperties()) {
			throw new NoIdPropertyException($this);
		}
		$object = MainObjectCollection::getInstance()->getObject($id, $this->modelName);
		
		if (is_null($object)) {
			try {
				$object = $this->_buildObjectFromId($id, false, false);
			} catch (NotSatisfiedRestrictionException $e) {
				return null;
			} catch (UnexpectedValueTypeException $e) {
				return null;
			}
			$newObject = true;
		} else if ($object->isLoaded() && !$forceLoad) {
			return $object;
		} else {
			$newObject = false;
		}
		
		try {
			$success = $object->load($propertiesFilter, $forceLoad);
			if (!$success && $newObject) {
				$object->reset(); // remove object from main object collection if needed
			}
			return $success ? $object : null;
		} catch (\Exception $e) {
			if ($newObject) {
				$object->reset(); // remove object from main object collection if needed
			}
			throw $e;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_export()
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, &$nullNodes, $isolate = false) {
		/** @var \Comhon\Object\UniqueObject $object */
		if (is_null($object)) {
			return null;
		}
		if ($object->getModel()->isAbstract()) {
			throw new AbstractObjectExportException($object->getModel()->getName());
		}
		if (!$isFirstLevel || $interfacer->mustValidate()) {
			$object->validate();
		}
		
		$node              = $interfacer->createNode($nodeName);
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$onlyUpdatedValues = $isFirstLevel && $interfacer->hasToExportOnlyUpdatedValues();
		$propertiesFilter  = $isFirstLevel ? $this->_getPropertiesFilter($object, $interfacer) : null;
		
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
		if ($isolate) {
			$originalCollection = $objectCollectionInterfacer;
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
		}
		
		$properties = $object->getModel()->_getContextProperties($private);
		foreach ($object->getValues() as $propertyName => $value) {
			try {
				if (array_key_exists($propertyName, $properties)) {
					$property = $properties[$propertyName];
					
					if (
						$property->isExportable($private, $isSerialContext, $value)
						&& (!$onlyUpdatedValues || $property->isId() || $object->isUpdatedValue($propertyName))
						&& (is_null($propertiesFilter) || in_array($propertyName, $propertiesFilter))
					) {
						$propertyName  = $isSerialContext ? $property->getSerializationName() : $propertyName;
						if (is_null($value) && !is_null($nullNodes)) {
							// if $nullNodes is not null interfacer must be a xml interfacer
							$exportedValue = $interfacer->createNode($propertyName);
							$nullNodes[] = $exportedValue;
						} else {
							$exportedValue = $property->getModel()->_export($value, $propertyName, $interfacer, false, $objectCollectionInterfacer, $nullNodes, $property->isIsolated());
						}
						$interfacer->setValue($node, $exportedValue, $propertyName, $property->isInterfacedAsNodeXml());
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
								$idValue = $idProperty->getModel()->_export($idValue, $serializationName, $interfacer, false, $objectCollectionInterfacer, $nullNodes);
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
		if ($isolate) {
			if ($interfacer->hasToVerifyReferences()) {
				$this->_verifyReferences($object, $objectCollectionInterfacer);
			}
			$objectCollectionInterfacer = $originalCollection;
			$objectCollectionInterfacer->addObject($object, false);
		}
		self::$instanceObjectHash[spl_object_hash($object)]--;
		return $node;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return array|NULL
	 */
	private function _getPropertiesFilter(UniqueObject $object, Interfacer $interfacer) {
		$properties = $interfacer->getPropertiesFilter();
		if (is_null($properties)) {
			return $properties;
		}
		// at least ids must be in filter properties
		foreach ($object->getModel()->getIdProperties() as $propertyName => $property) {
			$properties[] = $propertyName;
		}
		return $properties;
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
	protected function _exportId(AbstractComhonObject $object, $nodeName, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer, &$nullNodes) {
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
			$exportedId = self::_toInterfacedId($object);
		} else {
			if (!$model->isInheritedFrom($this)) {
				throw new UnexpectedModelException($this, $model);
			}
			$exportedId = $interfacer->createNode($nodeName);
			$interfacer->setValue($exportedId, self::_toInterfacedId($object), Interfacer::COMPLEX_ID_KEY);
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
	private function _getInheritedModelName($interfacedObject, Interfacer $interfacer, $isFirstLevel) {
		if ($isFirstLevel && $interfacer->isSerialContext()) {
			$inheritance = $this->getSerialization() && $this->getSerialization()->getInheritanceKey()
				? $interfacer->getValue($interfacedObject, $this->getSerialization()->getInheritanceKey())
				: $interfacer->getValue($interfacedObject, Interfacer::INHERITANCE_KEY);
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
	private function _getInheritedModel($inheritanceModelName) {
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
			return $this->uniqueIdProperty->getModel()->importValue($id, $interfacer);
		}
		$idValues = [];
		$hasIds = false;
		foreach ($this->getIdProperties() as $idProperty) {
			if ($idProperty->isInterfaceable($private, $isSerialContext)) {
				$propertyName = $isSerialContext ? $idProperty->getSerializationName() : $idProperty->getName();
				if ($interfacer->hasValue($interfacedObject, $propertyName, $idProperty->isInterfacedAsNodeXml())) {
					$hasIds = true;
				}
				$idValue = $interfacer->getValue($interfacedObject, $propertyName, $idProperty->isInterfacedAsNodeXml());
				$idValues[] = $idProperty->getModel()->importValue($idValue, $interfacer);
			} else {
				$idValues[] = null;
			}
		}
		return $hasIds ? self::encodeId($idValues) : null;
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
				if ($isFirstLevel) {
					if ($interfacer->getMergeType() == Interfacer::OVERWRITE) {
						$isLoaded = $object->isLoaded();
						$object->reset(false);
						$object->setIsLoaded($isLoaded);
					}
				} elseif (!$isForeign) {
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
		try {
			return $this->_importRoot($interfacedObject, $interfacer);
		}
		catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_importRoot()
	 * @return \Comhon\Object\ComhonObject
	 */
	protected function _importRoot($interfacedObject, Interfacer $interfacer, AbstractComhonObject $rootObject = null, $isolate = false) {
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isNodeValue($interfacedObject)) {
			if (($interfacer instanceof StdObjectInterfacer) && is_array($interfacedObject) && empty($interfacedObject)) {
				$interfacedObject = new \stdClass();
			} else {
				throw new IncompatibleValueException($interfacedObject, $interfacer);
			}
		}
		
		return parent::_importRoot($interfacedObject, $interfacer, $rootObject, $isolate);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_getRootObject()
	 */
	protected function _getRootObject($interfacedObject, Interfacer $interfacer) {
		return $this->_getOrCreateObjectInstanceFromInterfacedObject(
			$interfacedObject,
			$interfacer,
			true,
			new ObjectCollectionInterfacer()
		);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_initObjectCollectionInterfacer()
	 */
	protected function _initObjectCollectionInterfacer(AbstractComhonObject $object, $mergeType) {
		$objectCollectionInterfacer = $mergeType == Interfacer::MERGE
			? new ObjectCollectionInterfacer($object)
			: new ObjectCollectionInterfacer();
		
		$objectCollectionInterfacer->addStartObject($object, false);
		$objectCollectionInterfacer->addObject($object, false);
		
		return $objectCollectionInterfacer;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_import()
	 */
	protected function _import(
		$interfacedObject,
		Interfacer $interfacer,
		$isFirstLevel,
		ObjectCollectionInterfacer $objectCollectionInterfacer,
		$isolate = false
	) {
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
		
		if ($isolate) {
			$objectCollectionInterfacer = $isFirstLevel
				? new ObjectCollectionInterfacer($object)
				: new ObjectCollectionInterfacer();
			$objectCollectionInterfacer->addStartObject($object, false);
			$objectCollectionInterfacer->addObject($object, false);
		}
		$this->_fillObject($object, $interfacedObject, $interfacer, $isFirstLevel, $objectCollectionInterfacer);
		if ($isolate) {
			if ($interfacer->hasToVerifyReferences()) {
				$this->_verifyReferences($object, $objectCollectionInterfacer);
			}
		}
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
			
			$imported = $model->_importRoot($interfacedObject, $interfacer, $object);
			if ($imported !== $object) {
				throw new ComhonException('invalid object instance');
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_fillObject()
	 */
	protected function _fillObject(
		AbstractComhonObject $object,
		$interfacedObject,
		Interfacer $interfacer,
		$isFirstLevel,
		ObjectCollectionInterfacer $objectCollectionInterfacer,
		$isolate = false
	) {
		if (!($object instanceof UniqueObject)) {
			throw new ArgumentException($object, UniqueObject::class, 1);
		}
		$model = $object->getModel();
		if (!$object->isA($this)) {
			throw new UnexpectedModelException($this, $model);
		}
		$processUnchangedValues = $isFirstLevel && $interfacer->hasToVerifyReferences() 
			&& $interfacer->getMergeType() == Interfacer::MERGE;
		if ($processUnchangedValues) {
			$unchangedValues = $object->getObjectValues();
			if (empty($unchangedValues)) {
				$processUnchangedValues = false;
			}
		}
		if ($isFirstLevel && $interfacer->hasToFlattenValues()) {
			$this->_unFlattenValues($interfacedObject, $object, $interfacer);
		}
		
		$private           = $interfacer->isPrivateContext();
		$isSerialContext   = $interfacer->isSerialContext();
		$flagAsUpdated     = $interfacer->hasToFlagValuesAsUpdated();
		$properties        = $model->_getContextProperties($private);
		$nullNodes         = $interfacer instanceof XMLInterfacer ? $interfacer->getNullNodes($interfacedObject) : null;
		
		foreach ($properties as $propertyName => $property) {
			try {
				if ($property->isInterfaceable($private, $isSerialContext)) {
					$interfacedPropertyName = $isSerialContext ? $property->getSerializationName() : $propertyName;
					if ($interfacer->hasValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml())) {
						$interfacedValue = $interfacer->getValue($interfacedObject, $interfacedPropertyName, $property->isInterfacedAsNodeXml());
						if ($interfacer->isNullValue($interfacedValue)) {
							$value = null;
						} else {
							$value = $property->getModel()->_import(
								$interfacedValue,
								$interfacer,
								$property->getModel()->_isNextLevelFirstLevel($isFirstLevel),
								$objectCollectionInterfacer,
								$property->isIsolated()
							);
						}
						$object->setValue($propertyName, $value, $flagAsUpdated);
						if ($processUnchangedValues) {
							unset($unchangedValues[$propertyName]);
						}
					} elseif (!$property->isInterfacedAsNodeXml() && !is_null($nullNodes) && in_array($interfacedPropertyName, $nullNodes)) {
						$object->setValue($propertyName, null, $flagAsUpdated);
						if ($processUnchangedValues) {
							unset($unchangedValues[$propertyName]);
						}
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
							$idPart = $idProperty->getModel()->importValue($idPart, $interfacer);
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
						if ($processUnchangedValues) {
							unset($unchangedValues[$propertyName]);
						}
					}
				}
				catch (ComhonException $e) {
					throw new ImportException($e, $propertyName);
				}
			}
		}
		
		if ($isFirstLevel) {
			if ($interfacer->hasToFlagObjectAsLoaded()) {
				$object->setIsLoaded(true);
			}
			if ($interfacer->mustValidate()) {
				$object->validate();
			}
			if ($processUnchangedValues) {
				$this->_processUnchangeValues($unchangedValues, $objectCollectionInterfacer);
			}
		} else {
			$object->setIsLoaded(true);
			$object->validate();
		}
	}
	
	/**
	 * add unchanged values of existing objects in new object collections.
	 * that will permit to verify references at the end of inport
	 * 
	 * @param array $unchangedValues
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 */
	private function _processUnchangeValues(array $unchangedValues, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		foreach ($unchangedValues as $name => $value) {
			$propertyModel = $this->getProperty($name)->getModel();
			if ($propertyModel instanceof ModelForeign) {
				if ($propertyModel->getModel() instanceof ModelArray) {
					foreach (ModelArray::getOneDimensionalValues($value, true) as $element) {
						$objectCollectionInterfacer->addObject($element, true);
					}
				} else {
					$objectCollectionInterfacer->addObject($value, true);
				}
			} else {
				ObjectCollection::build($value, true, false, $objectCollectionInterfacer->getNewObjectCollection());
			}
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
			if ($interfacer instanceof XMLInterfacer && $id instanceof \DOMElement) {
				$id = $interfacer->extractNodeText($id);
			}
		}
		if (!$model->hasIdProperties()) {
			throw new ComhonException("cannot import id, actual model '{$model->getName()}' doesn't have id");
		}
		if (!$interfacer->isPrivateContext() && $model->hasPrivateIdProperty()) {
			throw new ContextIdException();
		}
		if (is_null($id)) {
			return null;
		}
		if (
			$model->hasUniqueIdProperty() 
			&& (
				($isFirstLevel && $interfacer->isStringifiedValues())
				|| $interfacer instanceof NoScalarTypedInterfacer
			)
		) {
			$id = $model->getUniqueIdProperty()->getModel()->importValue($id, $interfacer);
		}
		if (is_object($id) || is_array($id) || $id === '') {
			$id = is_object($id) || is_array($id) ? json_encode($id) : $id;
			throw new ComhonException("malformed id '$id' for model '{$model->modelName}'");
		}
		
		return $model->_getOrCreateObjectInstance($id, $interfacer, false, true, $objectCollectionInterfacer);
	}
	
	/**
	 * build interface id from comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer|string
	 */
	private static function _toInterfacedId(UniqueObject $object) {
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
		if (!($value instanceof UniqueObject) || !$value->isA($this)) {
			$Obj = $this->getObjectInstance(false);
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		return true;
	}
	
}
