<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Property;

use Comhon\Object\ComhonArray;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelDateTime;
use Comhon\Object\ComhonDateTime;
use Comhon\Object\UniqueObject;
use Comhon\Exception\ComhonException;
use Comhon\Model\AbstractModel;
use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Model\Restriction\NotNull;
use Comhon\Model\Model;

class Property {

	/** @var \Comhon\Model\AbstractModel */
	protected $model;
	
	/** @var string */
	protected $name;
	
	/** @var boolean */
	protected $hasDefinedSerializationName;
	
	/** @var string */
	protected $serializationName;
	protected $isId;
	
	/** @var boolean */
	protected $isPrivate;
	
	/** @var boolean */
	protected $isRequired;
	
	/** @var boolean */
	protected $isSerializable;
	
	/** @var boolean */
	protected $isNotNull;
	
	/** @var boolean */
	protected $isIsolated;
	
	/** @var mixed */
	protected $default;
	
	/** @var boolean */
	protected $interfaceAsNodeXml;
	
	/** @var \Comhon\Model\Restriction\Restriction[] */
	protected $restrictions = [];
	
	/** @var string[] */
	protected $dependencies;
	
	/**
	 * 
	 * @param \Comhon\Model\AbstractModel $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isRequired
	 * @param boolean $isSerializable
	 * @param boolean $isNotNull
	 * @param mixed $default
	 * @param boolean $isInterfacedAsNodeXml
	 * @param \Comhon\Model\Restriction\Restriction[] $restrictions
	 * @param string[] $dependencies
	 * @param boolean $isIsolated
	 * @throws \Exception
	 */
	public function __construct(AbstractModel $model, $name, $serializationName = null, $isId = false, $isPrivate = false, $isRequired = false, $isSerializable = true, $isNotNull = false, $default = null, $isInterfacedAsNodeXml = null, $restrictions = [], $dependencies = [], $isIsolated = false) {
		$this->model = $model;
		$this->name = $name;
		$this->hasDefinedSerializationName = !is_null($serializationName);
		$this->serializationName = $this->hasDefinedSerializationName ? $serializationName : $this->name;
		$this->isId = $isId;
		$this->isPrivate = $isPrivate;
		$this->isRequired = $isRequired;
		$this->isSerializable = $isSerializable;
		$this->isNotNull = $isNotNull;
		$this->isIsolated = $isIsolated;
		$this->default = $default;
		$this->dependencies = $dependencies;
		
		if ($this->isIsolated && !($model instanceof Model)) {
			throw new ComhonException('only property with model instance of '.Model::class.' may be isolated');
		}
		foreach ($restrictions as $restriction) {
			if (!$restriction->isAllowedModel($this->model)) {
				throw new ComhonException('restriction doesn\'t allow specified model'.get_class($this->model));
			}
			$this->restrictions[get_class($restriction)] = $restriction;
		}
		
		if ($this->model instanceof SimpleModel) {
			$this->interfaceAsNodeXml = is_null($isInterfacedAsNodeXml) ? false : $isInterfacedAsNodeXml;
		} else {
			if (!is_null($isInterfacedAsNodeXml) && !$isInterfacedAsNodeXml) {
				trigger_error('warning! 8th parameter is ignored, property with complex model is inevitably interfaced as node xml');
			}
			// without inheritance, foreign property may be exported as attribute because only id is exported
			// but due to inheritance, model name can be exported with id so we need to export as node
			$this->interfaceAsNodeXml = true;
		}
		
		if ($this->isId && !($this->model instanceof SimpleModel)) {
			throw new ComhonException("property is defined as id, so argument 1 must be an instance of SimpleModel");
		}
	}
	
	/**
	 * get model
	 * 
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel|\Comhon\Model\ModelContainer
	 */
	public function getModel() {
		$this->model->load();
		return $this->model;
	}
	
	/**
	 * get model or model inside model container
	 * 
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getUniqueModel() {
		$uniqueModel = $this->getModel();
		if ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getUniqueModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
	}
	
	/**
	 * verify if model or model inside model container is a simple model
	 *
	 * @return bool
	 */
	public function isUniqueModelSimple() {
		return $this->model instanceof ModelContainer
			? $this->model->isUniqueModelSimple()
			: $this->model instanceof SimpleModel;
	}
	
	/**
	 * get name
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * get serialization name
	 *
	 * @return string
	 */
	public function getSerializationName() {
		return $this->serializationName;
	}
	
	/**
	 * verify if serialization has been defined in serialization manifest
	 *
	 * @return boolean
	 */
	public function hasDefinedSerializationName() {
		return $this->hasDefinedSerializationName;
	}
	
	/**
	 * verify if property is an id
	 *
	 * @return boolean
	 */
	public function isId() {
		return $this->isId;
	}
	
	/**
	 * verify if property private
	 *
	 * @return boolean
	 */
	public function isPrivate() {
		return $this->isPrivate;
	}
	
	/**
	 * verify if property value is required. 
	 * A loaded comhon object must have all its required values set (and not null).
	 *
	 * @return boolean
	 */
	public function isRequired() {
		return $this->isRequired;
	}
	
	/**
	 * verify if property is serializable
	 *
	 * @return boolean
	 */
	public function isSerializable() {
		return $this->isSerializable;
	}
	
	/**
	 * verify if property value must be not null
	 *
	 * @return boolean
	 */
	public function isNotNull() {
		return $this->isNotNull;
	}
	
	/**
	 * verify if property value is isolated
	 *
	 * @return boolean
	 */
	public function isIsolated() {
		return $this->isIsolated;
	}
	
	/**
	 * verify if property has default value
	 *
	 * @return boolean
	 */
	public function hasDefaultValue() {
		return !is_null($this->default);
	}
	
	/**
	 * get default value if exists
	 *
	 * @return mixed|null null if property doesn't have default value
	 */
	public function getDefaultValue() {
		if ($this->model instanceof ModelDateTime) {
			return new ComhonDateTime($this->default);
		}
		return $this->default;
	}
	
	/**
	 * verify if property is aggregation
	 *
	 * @return boolean
	 */
	public function isAggregation() {
		return false;
	}
	
	/**
	 * verify if property is foreign
	 *
	 * @return boolean
	 */
	public function isForeign() {
		return false;
	}
	
	/**
	 * verify if model property is complex
	 *
	 * @return boolean
	 */
	public function isComplex() {
		return $this->model->isComplex();
	}
	
	/**
	 * verify if property has model \Comhon\Model\ModelDateTime
	 *
	 * @return boolean
	 */
	public function hasModelDateTime() {
		return ($this->model instanceof ModelDateTime);
	}
	
	/**
	 * verifiy if property has several serialization names
	 * 
	 * @return boolean
	 */
	public function hasMultipleSerializationNames() {
		return false;
	}
	
	/**
	 * get restrictions
	 *
	 * @return \Comhon\Model\Restriction\Restriction[]
	 */
	public function getRestrictions() {
		return $this->restrictions;
	}
	
	/**
	 * verify if property depends on other properties. 
	 * a property depends on other properties if property value MAY be set only if other properties values are set.
	 *
	 * @return boolean
	 */
	public function hasDependencies() {
		return !empty($this->dependencies);
	}
	
	/**
	 * get names of dependency properties.
	 * dependencies values MUST be set when current property value is set
	 *
	 * @return mixed|null null if property doesn't have default value
	 */
	public function getDependencies() {
		return $this->dependencies;
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * 
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($private, $serialization) {
		return ($private || !$this->isPrivate) && (!$serialization || $this->isSerializable);
	}
	
	/**
	 * verify if value is exportable in public/private/serialization mode
	 * 
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @param mixed $value value that we want to export
	 * @return boolean true if value is exportable
	 */
	public function isExportable($private, $serialization, $value) {
		return $this->isInterfaceable($private, $serialization);
	}
	
	/**
	 * validate value regarding restrictions property.
	 * throw exception if value is not valid.
	 *
	 * @param mixed $value
	 */
	public function validate($value) {
		if (is_null($value)) {
			if ($this->isNotNull) {
				throw new NotSatisfiedRestrictionException($value, new NotNull());
			}
		} else {
			$this->getModel()->verifValue($value);
			if (!empty($this->restrictions)) {
				$restriction = Restriction::getFirstNotSatisifed($this->restrictions, $value);
				if (!is_null($restriction)) {
					throw new NotSatisfiedRestrictionException($value, $restriction);
				}
			}
		}
	}
	
	/**
	 * verify if value is valid regarding restrictions property
	 *
	 * @param mixed $value
	 * @return boolean true if property is valid
	 */
	public function isValid($value) {
		if (is_null($value)) {
			return !$this->isNotNull;
		}
		try {
			$this->getModel()->verifValue($value);
		} catch (\Exception $e) {
			return false;
		}
		if (empty($this->restrictions)) {
			return true;
		}
		return is_null(Restriction::getFirstNotSatisifed($this->restrictions, $value));
	}
	
	/**
	 * verify if property is exported/imported as node for xml export/import
	 * 
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfacedAsNodeXml() {
		return $this->interfaceAsNodeXml;
	}
	
	/**
	 * get aggregation properties names if exist
	 * 
	 * @return string[]|null null if there are no aggregation properties
	 */
	public function getAggregationProperties() {
		return null;
	}
	
	/**
	 * load specified value
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValue(UniqueObject $object, $propertiesFilter = [], $forceLoad = false) {
		throw new ComhonException('cannot load object, property is not foreign property');
	}
	
	/**
	 * load aggregation ids
	 * 
	 * @param \Comhon\Object\ComhonArray $object
	 * @param \Comhon\Object\UniqueObject $parentObject
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadAggregationIds(ComhonArray $object, UniqueObject $parentObject, $forceLoad = false) {
		throw new ComhonException('cannot load aggregation ids, property is not aggregation property');
	}
	
	/**
	 * get property model that permit to build literal
	 * 
	 * @return \Comhon\Model\SimpleModel|null
	 */
	public function getLiteralModel() {
		return ($this->getModel() instanceof SimpleModel) ? $this->getModel() : null;
	}
	
	/**
	 * verify if specified property is equal to this property
	 * 
	 * verify if properties are same instance or if they have same attributes
	 * 
	 * @param Property $property
	 * @return boolean
	 */
	public function isEqual(Property $property) {
		return $this === $property || (
			get_class($this)         === get_class($property) &&
			$this->name              === $property->getName() &&
			$this->isId              === $property->isId() &&
			$this->isPrivate         === $property->isPrivate() &&
			$this->isRequired        === $property->isRequired() &&
			$this->default           === $property->getDefaultValue() &&
			$this->isSerializable    === $property->isSerializable() &&
			$this->isNotNull         === $property->isNotNull() &&
			$this->isIsolated        === $property->isIsolated() &&
			$this->serializationName === $property->getSerializationName() &&
			$this->dependencies      === $property->getDependencies() &&
			$this->model->isEqual($property->getModel()) && 
			Restriction::compare($this->restrictions, $property->getRestrictions())
		);
	}
	
}
