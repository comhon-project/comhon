<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Interfacer;

use Comhon\Object\AbstractComhonObject;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Value\EnumerationException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Model\ModelComplex;

abstract class Interfacer {
	
	/**
	 * @var string preference name that define private context 
	 *     private properties are interfaced only in private context
	 */
	const PRIVATE_CONTEXT = 'privateContext';
	
	/**
	 * @var string define serial context 
	 *     - interface properies with their serialization name
	 *     - do not interface aggregations
	 *     - simplify foreign values with inherited main model
	 */
	const SERIAL_CONTEXT = 'serialContext';
	
	/**
	 * @var string preference name that define date time zone
	 */
	const DATE_TIME_ZONE = 'dateTimeZone';
	
	/**
	 * @var string preference name that define exported date format
	 */
	const DATE_TIME_FORMAT = 'dateTimeFormat';
	
	/**
	 * @var string preference name that define if only updated value will be exported
	 */
	const ONLY_UPDATED_VALUES = 'updatedValueOnly';
	
	/**
	 * @var string preference name that define filter for exported properties 
	 */
	const PROPERTIES_FILTERS = 'propertiesFilters';
	
	/**
	 * @var string preference name that define if complexes values have to be flatten
	 */
	const FLATTEN_VALUES = 'flattenValues';
	
	/**
	 * @var string preference name that define if simple values are string
	 *     available only during import for scalar interfacers
	 */
	const STRINGIFIED_VALUES = 'stringifiedValues';
	
	/**
	 * @var string preference name that define if imported values have to be flagged has updated
	 */
	const FLAG_VALUES_AS_UPDATED = 'flagValuesAsUpdated';
	
	/**
	 * @var string preference name that define if object created during import have to be flagged as loaded
	 */
	const FLAG_OBJECT_AS_LOADED = 'flagObjectAsLoaded';
	
	/**
	 * @var string preference name that define if interfacer must verify if foreign values are referenced 
	 * (i.e. if there is an existing value not foreign with same id) in interfaced object.
	 */
	const VERIFY_REFERENCES = 'verifyReferences';
	
	/**
	 * @var string preference name that define if interfacer must validate root object to interface
	 * 
	 * validation concern required properties, conflicts, dependencies and array size.
	 */
	const VALIDATE = 'validate';
	
	/**
	 * @var string preference name that define merge type during import
	 */
	const MERGE_TYPE = 'mergeType';
	
	/** @var integer */
	const MERGE = 1;
	
	/** @var integer */
	const OVERWRITE = 2;
	
	/** @var string */
	const INHERITANCE_KEY = 'inheritance-';
	
	/** @var string */
	const ASSOCIATIVE_KEY = 'key-';
	
	/** @var string */
	const COMPLEX_ID_KEY = 'id';
	
	/** @var string[] */
	const ALLOWED_MERGE_TYPE = [
		self::MERGE,
		self::OVERWRITE
	];
	
	/** @var boolean */
	private $privateContext = false;
	
	/** @var boolean */
	private $serialContext = false;
	
	/** @var string */
	private $dateTimeZone = null;
		
	/** @var string */
	private $dateTimeFormat = 'c';
	
	/** @var boolean */
	private $updatedValueOnly = false;
	
	/** @var string[] */
	private $propertiesFilters = null;
	
	/** @var boolean */
	private $flattenValues = false;
	
	/** @var boolean */
	private $StringifiedValues = false;
	
	/** @var integer */
	private $mergeType = self::MERGE;
	
	/** @var boolean */
	private $flagValuesAsUpdated = true;
	
	/** @var boolean */
	private $flagObjectAsLoaded = true;
	
	/** @var boolean */
	private $verifyReferences = true;
	
	/** @var boolean */
	private $validate = true;
	
	/**
	 *
	 * @param string $format must be one of [xml, json, yaml, application/json, application/xml, application/x-yaml]
	 * @param boolean $assoc used only for json and yaml format.
	 *                       if false return StdObjectInterfacer instance,
	 *                       AssocArrayInterfacer instance otherwise.
	 */
	public static function getInstance($format, $assoc = false) {
		switch ($format) {
			case 'xml':
			case 'application/xml':
				return new XMLInterfacer();
				break;
			case 'json':
			case 'application/json':
			case 'yaml':
			case 'application/x-yaml':
				return $assoc ? new AssocArrayInterfacer($format) : new StdObjectInterfacer($format);
				break;
			default:
				throw new ArgumentException($format, 'string', 1, ['json', 'xml', 'yaml', 'application/json', 'application/xml', 'application/x-yaml']);
		}
	}
	
	/**
	 * verify if private properties have to be interfaced
	 * 
	 * @return boolean
	 */
	public function isPrivateContext() {
		return $this->privateContext;
	}
	
	/**
	 * define if private properties have to be interfaced
	 * 
	 * @param boolean $boolean
	 */
	public function setPrivateContext($boolean) {
		$this->privateContext = $boolean;
	}
	
	/**
	 * verify if interfacer is used in serial context (serialization / deserialization)
	 * 
	 * @return boolean
	 */
	public function isSerialContext() {
		return $this->serialContext;
	}
	
	/**
	 * define if interfacer is used in serial context (serialization / deserialization)
	 * 
	 * @param boolean $boolean if true, use properties serialization name, and ignore aggregations
	 */
	public function setSerialContext($boolean) {
		$this->serialContext = $boolean;
	}
	
	/**
	 * get date time zone
	 * 
	 * @return \DateTimeZone
	 */
	public function getDateTimeZone() {
		return $this->dateTimeZone;
	}
	
	/**
	 * set date time zone
	 * 
	 * @param string|\DateTimeZone $timeZone
	 */
	public function setDateTimeZone($timeZone) {
		$this->dateTimeZone = $timeZone instanceof \DateTimeZone ? $timeZone : new \DateTimeZone($timeZone);
	}
	
	/**
	 * set default date time zone
	 */
	public function setDefaultDateTimeZone() {
		$this->dateTimeZone = new \DateTimeZone(date_default_timezone_get());
	}
	
	/**
	 * get date time format
	 * 
	 * @return string
	 */
	public function getDateTimeFormat() {
		return $this->dateTimeFormat;
	}
	
	/**
	 * set date time format
	 * 
	 * @param string $dateTimeFormat
	 */
	public function setDateTimeFormat($dateTimeFormat) {
		$this->dateTimeFormat = $dateTimeFormat;
	}
	
	/**
	 * verify if has to export only updated values
	 * 
	 * @return boolean
	 */
	public function hasToExportOnlyUpdatedValues() {
		return $this->updatedValueOnly;
	}
	
	/**
	 * define if has to export only updated values
	 * 
	 * @param boolean $boolean
	 */
	public function setExportOnlyUpdatedValues($boolean) {
		$this->updatedValueOnly = $boolean;
	}
	
	/**
	 * verify if has properties filter
	 * 
	 * @return boolean $boolean
	 */
	public function hasPropertiesFilter() {
		return !is_null($this->propertiesFilters);
	}
	
	/**
	 * get properties filter
	 * 
	 * @return string[]|null return null if there is no filter
	 */
	public function getPropertiesFilter() {
		return $this->propertiesFilters;
	}
	
	/**
	 * set properties filter
	 * 
	 * @param string[]|null $propertiesNames
	 */
	public function setPropertiesFilter(array $propertiesNames = null) {
		$this->propertiesFilters = $propertiesNames;
	}
	
	/**
	 * define if complex values have to be flatten
	 * 
	 * @param boolean $boolean
	 */
	public function setFlattenValues($boolean) {
		$this->flattenValues = $boolean;
	}
	
	/**
	 * verify if complex values have to be flatten
	 * 
	 * @return boolean
	 */
	public function hasToFlattenValues() {
		return $this->flattenValues;
	}
	
	/**
	 * define if interfaced simple values are stringified and must be casted during import
	 *
	 * @param boolean $boolean
	 */
	public function setStringifiedValues($boolean) {
		$this->StringifiedValues = $boolean;
	}
	
	/**
	 * verify if interfaced simple values are stringified and must be casted during import
	 */
	public function isStringifiedValues() {
		return $this->StringifiedValues;
	}
	
	/**
	 * define if imported values have to be flagged has updated
	 *
	 * @param boolean $boolean
	 */
	public function setFlagValuesAsUpdated($boolean) {
		$this->flagValuesAsUpdated = $boolean;
	}
	
	/**
	 * verify if imported values have to be flagged has updated
	 *
	 * @return boolean
	 */
	public function hasToFlagValuesAsUpdated() {
		return $this->flagValuesAsUpdated;
	}
	
	/**
	 * define if object created during import have to be flagged as loaded
	 *
	 * @param boolean $boolean
	 */
	public function setFlagObjectAsLoaded($boolean) {
		$this->flagObjectAsLoaded = $boolean;
	}
	
	/**
	 * verify if object created during import have to be flagged as loaded
	 *
	 * @return boolean
	 */
	public function hasToFlagObjectAsLoaded() {
		return $this->flagObjectAsLoaded;
	}
	
	/**
	 * define if interfacing must verify if foreign values are referenced 
	 * (i.e. if there is an existing value not foreign with same id) in interfaced object.
	 * if true given, when interfacing foreign value without reference, an exception is thrown.
	 * values with main model are not concerned.
	 *
	 * @param boolean $boolean
	 */
	public function setVerifyReferences($boolean) {
		$this->verifyReferences = $boolean;
	}
	
	/**
	 * verify if interfacing must verify if foreign values are referenced 
	 * (i.e. if there is an existing value not foreign with same id) in interfaced object.
	 *
	 * @return boolean
	 */
	public function hasToVerifyReferences() {
		return $this->verifyReferences;
	}
	
	/**
	 * define if interfacing must validate root object to interface.
	 * if true given, when interfacing object not valid, an exception is thrown.
	 *
	 * validation concern required properties, conflicts, dependencies and array size.
	 *
	 * @param boolean $boolean
	 */
	public function setValidate($boolean) {
		$this->validate = $boolean;
	}
	
	/**
	 * verify if interfacing must validate root object to interface.
	 * 
	 * validation concern required properties, conflicts, dependencies and array size.
	 *
	 * @return boolean
	 */
	public function mustValidate() {
		return $this->validate;
	}
	
	/**
	 * define merge type to apply during import
	 * 
	 * @param integer $mergeType possible values are [self::MERGE, self::OVERWRITE]
	 */
	public function setMergeType($mergeType) {
		if (!in_array($mergeType, self::ALLOWED_MERGE_TYPE, true)) {
			throw new ArgumentException($mergeType, self::ALLOWED_MERGE_TYPE, 1);
		}
		$this->mergeType = $mergeType;
	}
	
	/**
	 * get merge type to apply during import
	 *
	 * @return integer
	 */
	public function getMergeType() {
		return $this->mergeType;
	}
	
	/**
	 * get media type associated to exported format
	 * 
	 * @return string
	 */
	abstract public function getMediaType();
	
	/**
	 * get value in $node with property $name
	 * 
	 * @param mixed $node
	 * @param string $name
	 * @param boolean $asNode
	 * @return mixed null if doesn't exist
	 */
	abstract public function &getValue(&$node, $name, $asNode = false);
	
	/**
	 * verify if $node contain value with property $name
	 * 
	 * @param mixed $node
	 * @param string $name
	 * @param boolean $asNode
	 * @return boolean
	 */
	abstract public function hasValue($node, $name, $asNode = false);
	
	/**
	 * verify if value is null
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isNullValue($value);
	
	/**
	 * get traversable node
	 * 
	 * actually this method has interest only for XMLInterfacer that need to retrieve children nodes, 
	 * other interfacer only return $node passed in parameter
	 *
	 * @param mixed $node
	 * @return mixed
	 */
	abstract public function getTraversableNode($node);
	
	/**
	 * verify if value is expected node type
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isNodeValue($value);
	
	/**
	 * verify if value is an array node
	 * 
	 * @param mixed $value
	 * @param boolean $isAssociative
	 * @return boolean
	 */
	abstract public function isArrayNodeValue($value, $isAssociative);
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isComplexInterfacedId($value);
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isFlattenComplexInterfacedId($value);
	
	/**
	 * set value in $node with property $name
	 * 
	 * @param mixed $node
	 * @param mixed $value
	 * @param string $name
	 * @param boolean $asNode
	 */
	abstract public function setValue(&$node, $value, $name = null, $asNode = false);
	
	/**
	 * unset value in $node with property $name
	 *
	 * @param mixed $node
	 * @param string $name
	 * @param boolean $asNode
	 */
	abstract public function unsetValue(&$node, $name, $asNode = false);
	
	/**
	 * add value to $node
	 *
	 * @param mixed $node
	 * @param mixed $value
	 * @param string $name
	 */
	abstract public function addValue(&$node, $value, $name = null);
	
	/**
	 * add value to $node
	 *
	 * @param mixed $node
	 * @param mixed $value
	 * @param string $key
	 * @param string $name
	 */
	abstract public function addAssociativeValue(&$node, $value, $key, $name = null);
	
	/**
	 * create node
	 * 
	 * @param string $name
	 * @return mixed
	 */
	abstract public function createNode($name = null);
	
	/**
	 * get node classes
	 *
	 * @return string[]
	 */
	abstract public function getNodeClasses();
	
	/**
	 * create array node
	 * 
	 * @param string $name
	 * @return mixed
	 */
	abstract public function createArrayNode($name = null);
	
	/**
	 * get array node classes
	 *
	 * @return string[]
	 */
	abstract public function getArrayNodeClasses();
	
	/**
	 * transform given node to string
	 *
	 * @param mixed $node
	 * @param bool $prettyPrint
	 * @return string
	 */
	abstract public function toString($node, $prettyPrint = false);
	
	/**
	 * transform given string to node
	 *
	 * @param mixed $string
	 * @return mixed
	 */
	abstract public function fromString($string);
	
	/**
	 * write file with given content
	 * 
	 * @param mixed $node
	 * @param string $path
	 * @param bool $prettyPrint
	 * @return boolean
	 */
	abstract public function write($node, $path, $prettyPrint = false);
	
	/**
	 * read file and load node with file content
	 * 
	 * @param string $path
	 * @return mixed|null return null on failure
	 */
	abstract public function read($path);
	
	/**
	 * flatten value (transform object/array to string)
	 * 
	 * @param mixed $node
	 * @param string $name
	 */
	abstract public function flattenNode(&$node, $name);
	
	/**
	 * unflatten value (transform string to object/array)
	 * 
	 * @param array $node
	 * @param string $name
	 */
	abstract public function unFlattenNode(&$node, $name);
	
	/**
	 * replace value in property $name by $value (fail if property $name doesn't exist)
	 * 
	 * @param mixed $node
	 * @param string $name
	 * @param mixed $value value to place in key $name
	 */
	abstract public function replaceValue(&$node, $name, $value);
	
	/**
	 * verify if interfaced object has typed scalar values (int, float, string...).
	 * 
	 * @return boolean
	 */
	public function hasScalarTypedValues() {
		return true;
	}
	
	/**
	 * export given comhon object to interfaced object 
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param array $preferences
	 * @return mixed
	 */
	public function export(AbstractComhonObject $object, $preferences = []) {
		$this->setPreferences($preferences);
		try {
			return $object->export($this);
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
	}
	
	/**
	 * import given node and construct comhon object
	 * 
	 * @param mixed $node
	 * @param \Comhon\Model\Model|\Comhon\Model\ModelArray $model
	 * @param array $preferences
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	public function import($node, ModelComplex $model, array $preferences = []) {
		$this->setPreferences($preferences);
		try {
			return $model->import($node, $this);
		} catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	/**
	 *
	 * @param mixed $node
	 */
	public function setPreferences(array $preferences) {
		// private
		if (array_key_exists(self::PRIVATE_CONTEXT, $preferences)) {
			if (!is_bool($preferences[self::PRIVATE_CONTEXT])) {
				throw new UnexpectedValueTypeException($preferences[self::PRIVATE_CONTEXT], 'boolean', self::PRIVATE_CONTEXT);
			}
			$this->setPrivateContext($preferences[self::PRIVATE_CONTEXT]);
		}
		
		// serial context
		if (array_key_exists(self::SERIAL_CONTEXT, $preferences)) {
			if (!is_bool($preferences[self::SERIAL_CONTEXT])) {
				throw new UnexpectedValueTypeException($preferences[self::SERIAL_CONTEXT], 'boolean', self::SERIAL_CONTEXT);
			}
			$this->setSerialContext($preferences[self::SERIAL_CONTEXT]);
		}
		
		// date time zone
		if (array_key_exists(self::DATE_TIME_ZONE, $preferences)) {
			if (!is_string($preferences[self::DATE_TIME_ZONE])) {
				throw new UnexpectedValueTypeException($preferences[self::DATE_TIME_ZONE], 'string', self::DATE_TIME_ZONE);
			}
			$this->setDateTimeZone($preferences[self::DATE_TIME_ZONE]);
		}
		
		// date time format
		if (array_key_exists(self::DATE_TIME_FORMAT, $preferences)) {
			if (!is_string($preferences[self::DATE_TIME_FORMAT])) {
				throw new UnexpectedValueTypeException($preferences[self::DATE_TIME_FORMAT], 'string', self::DATE_TIME_FORMAT);
			}
			$this->setDateTimeFormat($preferences[self::DATE_TIME_FORMAT]);
		}
		
		// only updated values
		if (array_key_exists(self::ONLY_UPDATED_VALUES, $preferences)) {
			if (!is_bool($preferences[self::ONLY_UPDATED_VALUES])) {
				throw new UnexpectedValueTypeException($preferences[self::ONLY_UPDATED_VALUES], 'boolean', self::ONLY_UPDATED_VALUES);
			}
			$this->setExportOnlyUpdatedValues($preferences[self::ONLY_UPDATED_VALUES]);
		}
		
		// preoperties filters
		if (array_key_exists(self::PROPERTIES_FILTERS, $preferences)) {
			if (!is_null($preferences[self::PROPERTIES_FILTERS]) && !is_array($preferences[self::PROPERTIES_FILTERS])) {
				throw new UnexpectedValueTypeException($preferences[self::PROPERTIES_FILTERS], 'array', self::PROPERTIES_FILTERS);
			}
			$this->setPropertiesFilter($preferences[self::PROPERTIES_FILTERS]);
		}
		
		// flatten values
		if (array_key_exists(self::FLATTEN_VALUES, $preferences)) {
			if (!is_bool($preferences[self::FLATTEN_VALUES])) {
				throw new UnexpectedValueTypeException($preferences[self::FLATTEN_VALUES], 'boolean', self::FLATTEN_VALUES);
			}
			$this->setFlattenValues($preferences[self::FLATTEN_VALUES]);
		}
		
		// stringified values
		if (array_key_exists(self::STRINGIFIED_VALUES, $preferences)) {
			if (!is_bool($preferences[self::STRINGIFIED_VALUES])) {
				throw new UnexpectedValueTypeException($preferences[self::STRINGIFIED_VALUES], 'boolean', self::FLATTEN_VALUES);
			}
			$this->setFlattenValues($preferences[self::STRINGIFIED_VALUES]);
		}
		
		// flag values as updated
		if (array_key_exists(self::FLAG_VALUES_AS_UPDATED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_VALUES_AS_UPDATED])) {
				throw new UnexpectedValueTypeException($preferences[self::FLAG_VALUES_AS_UPDATED], 'boolean', self::FLAG_VALUES_AS_UPDATED);
			}
			$this->setFlagValuesAsUpdated($preferences[self::FLAG_VALUES_AS_UPDATED]);
		}
		
		// flag ComhonObject as updated
		if (array_key_exists(self::FLAG_OBJECT_AS_LOADED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_OBJECT_AS_LOADED])) {
				throw new UnexpectedValueTypeException($preferences[self::FLAG_OBJECT_AS_LOADED], 'boolean', self::FLAG_OBJECT_AS_LOADED);
			}
			$this->setFlagObjectAsLoaded($preferences[self::FLAG_OBJECT_AS_LOADED]);
		}
		
		// verify foreign values references
		if (array_key_exists(self::VERIFY_REFERENCES, $preferences)) {
			if (!is_bool($preferences[self::VERIFY_REFERENCES])) {
				throw new UnexpectedValueTypeException($preferences[self::VERIFY_REFERENCES], 'boolean', self::VERIFY_REFERENCES);
			}
			$this->setVerifyReferences($preferences[self::VERIFY_REFERENCES]);
		}
		
		// validate root object
		if (array_key_exists(self::VALIDATE, $preferences)) {
			if (!is_bool($preferences[self::VALIDATE])) {
				throw new UnexpectedValueTypeException($preferences[self::VALIDATE], 'boolean', self::VALIDATE);
			}
			$this->setValidate($preferences[self::VALIDATE]);
		}
		
		// merge type
		if (array_key_exists(self::MERGE_TYPE, $preferences)) {
			if (!in_array($preferences[self::MERGE_TYPE], self::ALLOWED_MERGE_TYPE, true)) {
				throw new EnumerationException($preferences[self::MERGE_TYPE], self::ALLOWED_MERGE_TYPE, self::MERGE_TYPE);
			}
			$this->setMergeType($preferences[self::MERGE_TYPE]);
		}
	}
	
}
