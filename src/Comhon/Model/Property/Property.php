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
use Comhon\Object\ComhonObject;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelDateTime;
use Comhon\Object\ComhonDateTime;
use Comhon\Model\Model;

class Property {

	protected $model;
	protected $name;
	protected $serializationName;
	protected $isId;
	protected $isPrivate;
	protected $isSerializable;
	protected $default;
	protected $interfaceAsNodeXml;
	
	/**
	 * 
	 * @param Model $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 * @param mixed $default
	 * @param unknown $restriction
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
			throw new \Exception("id property with name '$name' must be a simple model");
		}
	}
	
	/**
	 * @return Model
	 */
	public function getModel() {
		$this->model->load();
		return $this->model;
	}
	
	public function getUniqueModel() {
		$uniqueModel = $this->getModel();
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getSerializationName() {
		return $this->serializationName;
	}
	
	public function isId() {
		return $this->isId;
	}
	
	public function isPrivate() {
		return $this->isPrivate;
	}
	
	public function isSerializable() {
		return $this->isSerializable;
	}
	
	public function hasDefaultValue() {
		return !is_null($this->default);
	}
	
	public function getDefaultValue() {
		if ($this->model instanceof ModelDateTime) {
			return new ComhonDateTime($this->default);
		}
		return $this->default;
	}
	
	public function getSerialization() {
		return null;
	}
	
	public function isAggregation() {
		return false;
	}
	
	public function isForeign() {
		return false;
	}
	
	public function isComplex() {
		return $this->model->isComplex();
	}
	
	public function hasModelDateTime() {
		return ($this->model instanceof ModelDateTime);
	}
	
	/**
	 * verifiy if property has several serialization names
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
	 * verify if property is exportable in public/private/serialization mode
	 * 
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @param mixed $value value that we want to export
	 * @return boolean true if property is interfaceable
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
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfacedAsNodeXml() {
		return $this->interfaceAsNodeXml;
	}
	
	public function getAggregationProperties() {
		return null;
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValue(ComhonObject $object, $propertiesFilter = [], $forceLoad = false) {
		throw new \Exception('cannot load object, property is not foreign property');
	}
	
	/**
	 * 
	 * @param ObjectArray $object
	 * @param ComhonObject $parentObject
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValueIds(ObjectArray $object, ComhonObject $parentObject, $forceLoad = false) {
		throw new \Exception('cannot load aggregation ids, property is not aggregation property');
	}
	
	/**
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