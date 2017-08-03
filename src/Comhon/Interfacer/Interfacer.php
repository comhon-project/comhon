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

use Comhon\Object\ComhonObject;
use Comhon\Model\Model;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\MainModel;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Exception\EnumerationException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Interfacer\ExportException;

abstract class Interfacer {
	
	/**
	 * @var string preference name that define private context 
	 *     private properties are interfaced only in private context
	 */
	const PRIVATE_CONTEXT        = 'privateContext';
	
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
	 * @var string preference name that define if foreign object with main model have to be exported
	 */
	const EXPORT_MAIN_FOREIGN_OBJECTS = 'exportMainForeignObjects';
	
	/**
	 * @var string preference name that define if imported values have to be flagged has updated
	 */
	const FLAG_VALUES_AS_UPDATED = 'flagValuesAsUpdated';
	
	/**
	 * @var string preference name that define if object created during import have to be flagged as loaded
	 */
	const FLAG_OBJECT_AS_LOADED = 'flagObjectAsUpdated';
	
	/**
	 * @var string preference name that define merge type during import
	 */
	const MERGE_TYPE = 'mergeType';
	
	/** @var integer */
	const MERGE = 1;
	
	/** @var integer */
	const OVERWRITE = 2;
	
	/** @var integer */
	const NO_MERGE = 3;
	
	
	/** @var string */
	const __UNLOAD__ = '__UNLOAD__';
	
	/** @var string */
	const INHERITANCE_KEY = '__inheritance__';
	
	/** @var string */
	const COMPLEX_ID_KEY = 'id';
	
	/** @var string[] */
	private static $allowedMergeTypes = [
		self::MERGE,
		self::OVERWRITE,
		self::NO_MERGE
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
	private $propertiesFilters = [];
	
	/** @var boolean */
	private $flattenValues = false;
	
	/** @var integer */
	private $mergeType = self::MERGE;
	
	/** @var boolean */
	private $flagValuesAsUpdated = true;
	
	/** @var boolean */
	private $flagObjectAsLoaded = true;
	
	/** @var boolean */
	private $exportMainForeignObjects = false;
	
	/** @var mixed */
	protected $mainForeignObjects = null;
	
	/** @var array */
	protected $mainForeignIds = null;
	
	final public function __construct() {
		$this->dateTimeZone = new \DateTimeZone(date_default_timezone_get());
		$this->_initInstance();
	}
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * 
	 * @throws \Exception
	 */
	protected function _initInstance() {
		// called in final constructor
		// override this function if some stuff have to be done during instanciation
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
	 * @param string $timeZone
	 */
	public function setDateTimeZone($timeZone) {
		$this->dateTimeZone = new \DateTimeZone($timeZone);
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
	 * verify if has properties filter for specified model
	 * 
	 * @param string $modelName
	 * @return boolean $boolean
	 */
	public function hasPropertiesFilter($modelName) {
		return array_key_exists($modelName, $this->propertiesFilters);
	}
	
	/**
	 * get properties filter for specified model (properties names are stored in array keys)
	 * 
	 * @param string $modelName
	 * @return array|null return null if filter doesn't exist for specified model
	 */
	public function getPropertiesFilter($modelName) {
		return array_key_exists($modelName, $this->propertiesFilters)
		? $this->propertiesFilters[$modelName]
		: null;
	}
	
	/**
	 * reset properties filter
	 */
	public function resetPropertiesFilters() {
		$this->propertiesFilters = [];
	}
	
	/**
	 * set properties filter for specified model
	 * 
	 * @param string[] $propertiesNames
	 * @param string $modelName
	 */
	public function setPropertiesFilter($propertiesNames, $modelName) {
		if (is_array($propertiesNames)) {
			$this->propertiesFilters[$modelName] = array_flip($propertiesNames);
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			foreach ($model->getIdProperties()as $propertyName => $property) {
				$this->propertiesFilters[$modelName][$propertyName] = null;
			}
		}
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
	 * verify if has to export main foreign objects
	 * 
	 * @return boolean
	 */
	public function hasToExportMainForeignObjects() {
		return $this->exportMainForeignObjects;
	}
	
	/**
	 * define if has to export main foreign objects
	 * 
	 * @param boolean $boolean
	 */
	public function setExportMainForeignObjects($boolean) {
		$this->exportMainForeignObjects = $boolean;
	}
	
	/**
	 * initialize export
	 */
	public function initializeExport() {
		$this->mainForeignObjects = $this->exportMainForeignObjects ? $this->createNode('objects') : null;
		$this->mainForeignIds = $this->exportMainForeignObjects ? [] : null;
	}
	
	/**
	 * finalize export
	 * 
	 * @param mixed $rootNode
	 */
	public function finalizeExport($rootNode) {
		// do nothing (overrided in XMLInterfacer)
	}
	
	/**
	 * add exported main foreign object
	 * 
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param \Comhon\Model\Model $model
	 */
	public function addMainForeignObject($node, $nodeId, Model $model) {
		if (!is_null($this->mainForeignObjects)) {
			$modelName = $model->getName();
			if (!$this->hasValue($this->mainForeignObjects, $modelName, true)) {
				$this->setValue($this->mainForeignObjects, $this->createNode($modelName), $modelName);
			}
			$this->setValue($this->getValue($this->mainForeignObjects, $modelName, true), $node, $nodeId);
		}
	}
	
	/**
	 * remove exported main foreign object
	 *
	 * @param string|integer $nodeId
	 * @param \Comhon\Model\Model $model
	 */
	public function removeMainForeignObject($nodeId, Model $model) {
		if (!is_null($this->mainForeignObjects)) {
			$modelName = $model->getName();
			if ($this->hasValue($this->mainForeignObjects, $modelName, true)) {
				$this->unsetValue($this->getValue($this->mainForeignObjects, $modelName, true), $nodeId, true);
			}
		}
	}
	
	/**
	 * get exported main foreign objects
	 *
	 * @return array
	 */
	public function getMainForeignObjects() {
		return $this->mainForeignObjects;
	}
	
	/**
	 * verify if has main foreign objects with specified $modelName and $id
	 * 
	 * @param string $modelName
	 * @param string|integer $id
	 * @return boolean
	 */
	public function hasMainForeignObject($modelName, $id) {
		return !is_null($this->mainForeignObjects)
			&& $this->hasValue($this->mainForeignObjects, $modelName, true)
			&& $this->hasValue($this->getValue($this->mainForeignObjects, $modelName, true), $id, true);
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
	 * define merge type to apply during import
	 * 
	 * @param integer $mergeType possible values are [self::MERGE, self::OVERWRITE, self::NO_MERGE]
	 */
	public function setMergeType($mergeType) {
		if (!in_array($mergeType, self::$allowedMergeTypes, true)) {
			throw new ArgumentException($mergeType, self::$allowedMergeTypes, 1);
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
	 * actually this method has interest only for XMLInterfacer that need to retrieve children nodes
	 * other interfacer only return $node passed in parameter
	 *
	 * @param mixed $node
	 * @param boolean $getElementName only used for XMLInterfacer
	 * @return mixed
	 */
	abstract public function getTraversableNode($node, $getElementName = false);
	
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
	 * @return boolean
	 */
	abstract public function isArrayNodeValue($value);
	
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
	 * @return mixed
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
	 * create node
	 * 
	 * @param string $name
	 * return mixed
	 */
	abstract public function createNode($name = null);
	
	/**
	 * get node classes
	 *
	 * return string[]
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
	 * return string[]
	 */
	abstract public function getArrayNodeClasses();
	
	/**
	 * transform given node to string
	 * 
	 * @param mixed $node
	 * @return string
	 */
	abstract public function toString($node);
	
	/**
	 * write file with given content
	 * 
	 * @param mixed $node
	 * @param string $path
	 * @return boolean
	 */
	abstract public function write($node, $path);
	
	/**
	 * read file and load node with file content
	 * 
	 * @param string $path
	 * @return mixed|boolean return false on failure
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
		return !($this instanceof NoScalarTypedInterfacer);
	}
	
	/**
	 * export given comhon object to interfaced object 
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param array $preferences
	 * @return mixed
	 */
	public function export(ComhonObject $object, $preferences = []) {
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
	 * @param \Comhon\Model\MainModel $model
	 * @param array $preferences
	 * @return \Comhon\Object\ComhonObject
	 */
	public function import($node, MainModel $model, array $preferences = []) {
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
			if (!is_array($preferences[self::PROPERTIES_FILTERS])) {
				throw new UnexpectedValueTypeException($preferences[self::PROPERTIES_FILTERS], 'array', self::PROPERTIES_FILTERS);
			}
			$this->resetPropertiesFilters();
			foreach ($preferences[self::PROPERTIES_FILTERS] as $modelName => $properties) {
				$this->setPropertiesFilter($properties, $modelName);
			}
		}
		
		// flatten values
		if (array_key_exists(self::FLATTEN_VALUES, $preferences)) {
			if (!is_bool($preferences[self::FLATTEN_VALUES])) {
				throw new UnexpectedValueTypeException($preferences[self::FLATTEN_VALUES], 'boolean', self::FLATTEN_VALUES);
			}
			$this->setFlattenValues($preferences[self::FLATTEN_VALUES]);
		}
		
		// main foreign objects
		if (array_key_exists(self::EXPORT_MAIN_FOREIGN_OBJECTS, $preferences)) {
			if (!is_bool($preferences[self::EXPORT_MAIN_FOREIGN_OBJECTS])) {
				throw new UnexpectedValueTypeException($preferences[self::EXPORT_MAIN_FOREIGN_OBJECTS], 'boolean', self::EXPORT_MAIN_FOREIGN_OBJECTS);
			}
			$this->setExportMainForeignObjects($preferences[self::EXPORT_MAIN_FOREIGN_OBJECTS]);
		}
		
		// flag values as updated
		if (array_key_exists(self::FLAG_VALUES_AS_UPDATED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_VALUES_AS_UPDATED])) {
				throw new UnexpectedValueTypeException($preferences[self::FLAG_VALUES_AS_UPDATED], 'boolean', self::FLAG_VALUES_AS_UPDATED);
			}
			$this->setFlagValuesAsUpdated($preferences[self::FLAG_VALUES_AS_UPDATED]);
		}
		
		// flag Object as updated
		if (array_key_exists(self::FLAG_OBJECT_AS_LOADED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_OBJECT_AS_LOADED])) {
				throw new UnexpectedValueTypeException($preferences[self::FLAG_OBJECT_AS_LOADED], 'boolean', self::FLAG_OBJECT_AS_LOADED);
			}
			$this->setFlagObjectAsLoaded($preferences[self::FLAG_OBJECT_AS_LOADED]);
		}
		
		// merge type
		if (array_key_exists(self::MERGE_TYPE, $preferences)) {
			if (!in_array($preferences[self::MERGE_TYPE], self::$allowedMergeTypes, true)) {
				throw new EnumerationException($preferences[self::MERGE_TYPE], self::$allowedMergeTypes, self::MERGE_TYPE);
			}
			$this->setMergeType($preferences[self::MERGE_TYPE]);
		}
	}
	
}
