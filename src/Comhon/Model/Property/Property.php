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

use Comhon\Object\ObjectArray;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelDateTime;
use Comhon\Object\ComhonDateTime;
use Comhon\Model\Model;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\ComhonException;

class Property {

	/** @var \Comhon\Model\Model */
	protected $model;
	
	/** @var string */
	protected $name;
	
	/** @var string */
	protected $serializationName;
	protected $isId;
	
	/** @var boolean */
	protected $isPrivate;
	
	/** @var boolean */
	protected $isSerializable;
	
	/** @var mixed */
	protected $default;
	
	/** @var boolean */
	protected $interfaceAsNodeXml;
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 * @param mixed $default
	 * @param boolean $isInterfacedAsNodeXml
	 * @throws \Exception
	 */
	public function __construct(Model $model, $name, $serializationName = null, $isId = false, $isPrivate = false, $isSerializable = true, $default = null, $isInterfacedAsNodeXml = null) {
		$this->model = $model;
		$this->name = $name;
		$this->serializationName = is_null($serializationName) ? $this->name : $serializationName;
		$this->isId = $isId;
		$this->isPrivate = $isPrivate;
		$this->isSerializable = $isSerializable;
		$this->default = $default;
		
		if ($this->model instanceof SimpleModel) {
			$this->interfaceAsNodeXml = is_null($isInterfacedAsNodeXml) ? false : $isInterfacedAsNodeXml;
		} else {
			if (!is_null($isInterfacedAsNodeXml) && !$isInterfacedAsNodeXml) {
				trigger_error('warning! 8th parameter is ignored, property with complex model is inevitably interfaced as node xml');
			}
			// without inheritance foreign property may be exported as attribute because only id is exported
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
	 * @return \Comhon\Model\Model
	 */
	public function getModel() {
		$this->model->load();
		return $this->model;
	}
	
	/**
	 * get model or model inside model container
	 * 
	 * @return \Comhon\Model\Model
	 */
	public function getUniqueModel() {
		$uniqueModel = $this->getModel();
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
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
	 * verify if property is serializable
	 *
	 * @return boolean
	 */
	public function isSerializable() {
		return $this->isSerializable;
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
		return (is_null($value) || $this->getModel()->verifValue($value)) && $this->isInterfaceable($private, $serialization);
	}
	
	/**
	 * verify if value is satisfiable regarding restriction property
	 *
	 * @param mixed $value
	 * @param boolean $throwException
	 * @return boolean true if property is satisfiable
	 */
	public function isSatisfiable($value, $throwException = false) {
		return true;
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
	 * get aggregation properties if exists
	 * 
	 * @return Property[]|null null if there are no aggregation properties
	 */
	public function getAggregationProperties() {
		return null;
	}
	
	/**
	 * load specified value
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValue(ObjectUnique $object, $propertiesFilter = [], $forceLoad = false) {
		throw new ComhonException('cannot load object, property is not foreign property');
	}
	
	/**
	 * load aggregation ids
	 * 
	 * @param \Comhon\Object\ObjectArray $object
	 * @param \Comhon\Object\ObjectUnique $parentObject
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadAggregationIds(ObjectArray $object, ObjectUnique $parentObject, $forceLoad = false) {
		throw new ComhonException('cannot load aggregation ids, property is not aggregation property');
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
			get_class($this)          === get_class($property) &&
			$this->model             === $property->getModel() &&
			$this->name              === $property->getName() &&
			$this->isId              === $property->isId() &&
			$this->isPrivate         === $property->isPrivate() &&
			$this->default           === $property->getDefaultValue() &&
			$this->isSerializable    === $property->isSerializable() &&
			$this->serializationName === $property->getSerializationName()
		);
	}
}