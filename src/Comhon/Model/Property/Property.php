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

class Property {

	const ALLOWED_STRING_LITERALS = [
		'Comhon\Logic\Simple\Literal\String',
		'Comhon\Logic\Simple\Literal\Set\String',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_FLOAT_LITERALS = [
		'Comhon\Logic\Simple\Literal\Numeric\Float',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Float',
		'Comhon\Logic\Simple\Literal\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_INTEGER_LITERALS = [
		'Comhon\Logic\Simple\Literal\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Set\Numeric\Integer',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	const ALLOWED_BOOLEAN_LITERALS = [
		'Comhon\Logic\Simple\Literal\Boolean',
		'Comhon\Logic\Simple\Literal\Null'
	];
	
	protected static $allowedLiterals = [
		'string'     => self::ALLOWED_STRING_LITERALS,
		'integer'    => self::ALLOWED_INTEGER_LITERALS,
		'float'      => self::ALLOWED_FLOAT_LITERALS,
		'dateTime'   => self::ALLOWED_STRING_LITERALS,
		'index'      => self::ALLOWED_INTEGER_LITERALS,
		'percentage' => self::ALLOWED_FLOAT_LITERALS,
		'boolean'    => self::ALLOWED_BOOLEAN_LITERALS
	];
	
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
	
	/** @var mixed */
	protected $default;
	
	/** @var boolean */
	protected $interfaceAsNodeXml;
	
	/** @var \Comhon\Model\Restriction\Restriction[] */
	protected $restrictions = [];
	
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
	 * @throws \Exception
	 */
	public function __construct(AbstractModel $model, $name, $serializationName = null, $isId = false, $isPrivate = false, $isRequired = false, $isSerializable = true, $isNotNull = false, $default = null, $isInterfacedAsNodeXml = null, $restrictions = []) {
		$this->model = $model;
		$this->name = $name;
		$this->hasDefinedSerializationName = !is_null($serializationName);
		$this->serializationName = $this->hasDefinedSerializationName ? $serializationName : $this->name;
		$this->isId = $isId;
		$this->isPrivate = $isPrivate;
		$this->isRequired = $isRequired;
		$this->isSerializable = $isSerializable;
		$this->isNotNull = $isNotNull;
		$this->default = $default;
		
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
	 * @return \Comhon\Model\AbstractModel
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
	 * verify if property must be not null
	 *
	 * @return boolean
	 */
	public function isNotNull() {
		return $this->isNotNull;
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
	 * verify if value is satisfiable regarding restrictions property
	 *
	 * @param mixed $value
	 * @param boolean $throwException
	 * @return boolean true if property is satisfiable
	 */
	public function isSatisfiable($value, $throwException = false) {
		if (is_null($value)) {
			if ($this->isNotNull && $throwException) {
				throw new NotSatisfiedRestrictionException($value, new NotNull());
			}
			return !$this->isNotNull;
		}
		if (empty($this->restrictions)) {
			return true;
		}
		$restriction = Restriction::getFirstNotSatisifed($this->restrictions, $value);
		if (!is_null($restriction) && $throwException) {
			throw new NotSatisfiedRestrictionException($value, $restriction);
		}
		return is_null($restriction);
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
			$this->serializationName === $property->getSerializationName() &&
			$this->model->isEqual($property->getModel()) && 
			Restriction::compare($this->restrictions, $property->getRestrictions())
		);
	}
	
	/**
	 * verify if given literal is allowed.
	 * literals are used when requesting objects.
	 * model of given literal must be a 'Comhon\Logic\Simple\Literal'.
	 *
	 * @param UniqueObject $literal
	 * @return boolean
	 */
	public function isAllowedLiteral(UniqueObject $literal) {
		return array_key_exists($this->getUniqueModel()->getName(), self::$allowedLiterals)
		? in_array($literal->getModel()->getName(), self::$allowedLiterals[$this->getUniqueModel()->getName()])
		: false;
	}
	
	/**
	 * get allowed literals that may be applied.
	 * literals are used when requesting objects.
	 * model of given literal must be a 'Comhon\Logic\Simple\Literal'.
	 *
	 * @param UniqueObject $literal
	 * @return boolean
	 */
	public function getAllowedLiterals() {
		return array_key_exists($this->getUniqueModel()->getName(), self::$allowedLiterals)
		? self::$allowedLiterals[$this->getUniqueModel()->getName()]
		: [];
	}
	
}
