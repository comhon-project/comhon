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

abstract class Interfacer {
	
	const PRIVATE_CONTEXT        = 'privateContext';
	const SERIAL_CONTEXT         = 'serialContext';
	const DATE_TIME_ZONE         = 'dateTimeZone';
	const DATE_TIME_FORMAT       = 'dateTimeFormat';
	const ONLY_UPDATED_VALUES    = 'updatedValueOnly';
	const PROPERTIES_FILTERS     = 'propertiesFilters';
	const FLATTEN_VALUES         = 'flattenValues';
	const MAIN_FOREIGN_OBJECTS   = 'mainForeignObjects';
	const FLAG_VALUES_AS_UPDATED = 'flagValuesAsUpdated';
	const FLAG_OBJECT_AS_LOADED  = 'flagObjectAsUpdated';
	const MERGE_TYPE             = 'mergeType';
	
	const MERGE     = 1;
	const OVERWRITE = 2;
	const NO_MERGE  = 3;
	
	const __UNLOAD__ = '__UNLOAD__';
	const INHERITANCE_KEY = '__inheritance__';
	const COMPLEX_ID_KEY = 'id';
	
	private static $allowedMergeTypes = [
		self::MERGE,
		self::OVERWRITE,
		self::NO_MERGE
	];
	
	private $private             = false;
	private $serialContext       = false;
	private $dateTimeZone        = null;
	private $dateTimeFormat      = 'c';
	private $updatedValueOnly    = false;
	private $propertiesFilters   = [];
	private $flattenValues       = false;
	private $mergeType           = self::MERGE;
	private $flagValuesAsUpdated      = true;
	private $flagObjectAsLoaded       = true;
	private $exportMainForeignObjects = false;
	
	protected $mainForeignObjects  = null;
	protected $mainForeignIds      = null;
	
	final public function __construct() {
		$this->dateTimeZone = new \DateTimeZone(date_default_timezone_get());
		$this->_initInstance();
	}
	
	/**
	 * initialize DomDocument that permit to contruct nodes
	 * @throws \Exception
	 */
	protected function _initInstance() {
		// called in final constructor
		// override this function if some stuff have to be done during instanciation
	}
	
	/**
	 * verify if private properties have to be interfaced
	 * @return boolean
	 */
	public function isPrivateContext() {
		return $this->private;
	}
	
	/**
	 * define if private properties have to be interfaced
	 * @param boolean $boolean
	 */
	public function setPrivateContext($boolean) {
		$this->private = $boolean;
	}
	
	/**
	 * verify if interfacer is used in serial context (serialization / deserialization)
	 * @return boolean
	 */
	public function isSerialContext() {
		return $this->serialContext;
	}
	
	/**
	 * define if interfacer is used in serial context (serialization / deserialization)
	 * @param boolean $boolean if true, use properties serialization name, and ignore aggregations
	 */
	public function setSerialContext($boolean) {
		$this->serialContext = $boolean;
	}
	
	/**
	 * get date time zone
	 * @return \DateTimeZone
	 */
	public function getDateTimeZone() {
		return $this->dateTimeZone;
	}
	
	/**
	 * set date time zone
	 * @param string $timeZone
	 */
	public function setDateTimeZone($timeZone) {
		$this->dateTimeZone = new \DateTimeZone($timeZone);
	}
	
	/**
	 * set default date time zone
	 * @param string $timeZone
	 */
	public function setDefaultDateTimeZone() {
		$this->dateTimeZone = new \DateTimeZone(date_default_timezone_get());
	}
	
	/**
	 * get date time format
	 * @return string
	 */
	public function getDateTimeFormat() {
		return $this->dateTimeFormat;
	}
	
	/**
	 * set date time format
	 * @param string $dateTimeFormat
	 */
	public function setDateTimeFormat($dateTimeFormat) {
		$this->dateTimeFormat = $dateTimeFormat;
	}
	
	/**
	 * verify if has to export only updated values
	 * @return boolean
	 */
	public function hasToExportOnlyUpdatedValues() {
		return $this->updatedValueOnly;
	}
	
	/**
	 * define if has to export only updated values
	 * @param boolean $boolean
	 */
	public function setExportOnlyUpdatedValues($boolean) {
		$this->updatedValueOnly = $boolean;
	}
	
	/**
	 * verify if has properties filter for specified model
	 * @param string $modelName
	 * @return boolean $boolean
	 */
	public function hasPropertiesFilter($modelName) {
		return array_key_exists($modelName, $this->propertiesFilters);
	}
	
	/**
	 * get properties filter for specified model (properties names are stored in array keys)
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
	 * 
	 * @param boolean $boolean
	 */
	public function setFlattenValues($boolean) {
		$this->flattenValues = $boolean;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function hasToFlattenValues() {
		return $this->flattenValues;
	}
	
	/**
	 * verify if has to export main foreign objects
	 * @return boolean
	 */
	public function hasToExportMainForeignObjects() {
		return $this->exportMainForeignObjects;
	}
	
	/**
	 * define if has to export main foreign objects
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
	 * @param mixed $rootNode
	 */
	public function finalizeExport($rootNode) {
		// do nothing (overrided in XMLInterfacer)
	}
	
	/**
	 * 
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param Model $model
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
	 *
	 * @param mixed $node
	 * @param string|integer $nodeId
	 * @param Model $model
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
	 *
	 * @return array
	 */
	public function getMainForeignObjects() {
		return $this->mainForeignObjects;
	}
	
	/**
	 *
	 * @return array
	 */
	public function hasMainForeignObject($modelName, $id) {
		return !is_null($this->mainForeignObjects)
			&& $this->hasValue($this->mainForeignObjects, $modelName, true)
			&& $this->hasValue($this->getValue($this->mainForeignObjects, $modelName, true), $id, true);
	}
	
	/**
	 *
	 * @param boolean $boolean
	 */
	public function setFlagValuesAsUpdated($boolean) {
		$this->flagValuesAsUpdated = $boolean;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function hasToFlagValuesAsUpdated() {
		return $this->flagValuesAsUpdated;
	}
	
	/**
	 *
	 * @param boolean $boolean
	 */
	public function setFlagObjectAsLoaded($boolean) {
		$this->flagObjectAsLoaded = $boolean;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function hasToFlagObjectAsLoaded() {
		return $this->flagObjectAsLoaded;
	}
	
	/**
	 *
	 * @param integer $mergeType
	 */
	public function setMergeType($mergeType) {
		if (!in_array($mergeType, self::$allowedMergeTypes)) {
			throw new \Exception("merge type '$mergeType' not allowed");
		}
		$this->mergeType = $mergeType;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getMergeType() {
		return $this->mergeType;
	}
	
	/**
	 * 
	 * @param mixed $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return mixed
	 */
	abstract public function &getValue(&$node, $propertyName, $asNode = false);
	
	/**
	 * 
	 * @param mixed $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return boolean
	 */
	abstract public function hasValue($node, $propertyName, $asNode = false);
	
	/**
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isNullValue($value);
	
	/**
	 *
	 * @param mixed $node
	 * @param boolean $getElementName only used for XMLInterfacer
	 * @return mixed
	 */
	abstract public function getTraversableNode($node, $getElementName = false);
	
	/**
	 * verify if value is a node
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isNodeValue($value);
	
	/**
	 * verify if value is a array node
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function isArrayNodeValue($value);
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $value
	 * @return mixed
	 */
	abstract public function isComplexInterfacedId($value);
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * @param mixed $value
	 * @return mixed
	 */
	abstract public function isFlattenComplexInterfacedId($value);
	
	/**
	 * 
	 * @param mixed $node
	 * @param mixed $value
	 * @param string $name
	 * @param boolean $asNode
	 * @return mixed
	 */
	abstract public function setValue(&$node, $value, $name = null, $asNode = false);
	
	/**
	 *
	 * @param mixed $node
	 * @param string $name
	 * @param boolean $asNode
	 * @return mixed
	 */
	abstract public function unsetValue(&$node, $name, $asNode = false);
	
	/**
	 *
	 * @param mixed $node
	 * @param mixed $value
	 * @param string $name
	 * @return mixed
	 */
	abstract public function addValue(&$node, $value, $name = null);
	
	/**
	 * @param string $name
	 * return mixed
	 */
	abstract public function createNode($name = null);
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	abstract public function createNodeArray($name = null);
	
	/**
	 * transform given node to string
	 * @param mixed $node
	 * @return string
	 */
	abstract public function toString($node);
	
	/**
	 * write file with given content
	 * @param mixed $node
	 * @param string $path
	 * @return boolean
	 */
	abstract public function write($node, $path);
	
	/**
	 * read file and load node with file content
	 * @param string $path
	 * @return mixed|boolean return false on failure
	 */
	abstract public function read($path);
	
	/**
	 * flatten value (transform object/array to string)
	 * @param mixed $node
	 * @param string $name
	 */
	abstract public function flattenNode(&$node, $name);
	
	/**
	 * unflatten value (transform string to object/array)
	 * @param array $node
	 * @param string $name
	 */
	abstract public function unFlattenNode(&$node, $name);
	
	/**
	 * replace value
	 * @param mixed $node
	 * @param string $name
	 * @param mixed $value
	 */
	abstract public function replaceValue(&$node, $name, $value);
	
	/**
	 * verify if interfaced object has typed scalar values (int, float, string...).
	 * @return boolean
	 */
	public function hasScalarTypedValues() {
		return !($this instanceof NoScalarTypedInterfacer);
	}
	
	/**
	 * export given comhon object to interfaced object 
	 * @param ComhonObject $object
	 * @param array $preferences
	 */
	public function export(ComhonObject $object, $preferences = []) {
		$this->setPreferences($preferences);
		return $object->export($this);
	}
	
	/**
	 * import given node and construct comhon object
	 * @param mixed $node
	 * @param MainModel $model
	 * @param array $preferences
	 * @return ComhonObject
	 */
	public function import($node, MainModel $model, array $preferences = []) {
		$this->setPreferences($preferences);
		return $model->import($node, $this);
	}
	
	/**
	 *
	 * @param mixed $node
	 * @return boolean
	 */
	public function setPreferences(array $preferences) {
		// private
		if (array_key_exists(self::PRIVATE_CONTEXT, $preferences)) {
			if (!is_bool($preferences[self::PRIVATE_CONTEXT])) {
				throw new \Exception('preference "'.self::PRIVATE_CONTEXT.'" should be a boolean');
			}
			$this->setPrivateContext($preferences[self::PRIVATE_CONTEXT]);
		}
		
		// serial context
		if (array_key_exists(self::SERIAL_CONTEXT, $preferences)) {
			if (!is_bool($preferences[self::SERIAL_CONTEXT])) {
				throw new \Exception('preference "'.self::SERIAL_CONTEXT.'" should be a boolean');
			}
			$this->setSerialContext($preferences[self::SERIAL_CONTEXT]);
		}
		
		// date time zone
		if (array_key_exists(self::DATE_TIME_ZONE, $preferences)) {
			if (!is_string($preferences[self::DATE_TIME_ZONE])) {
				throw new \Exception('preference "'.self::DATE_TIME_ZONE.'" should be a string');
			}
			$this->setDateTimeZone($preferences[self::DATE_TIME_ZONE]);
		}
		
		// date time format
		if (array_key_exists(self::DATE_TIME_FORMAT, $preferences)) {
			if (!is_string($preferences[self::DATE_TIME_FORMAT])) {
				throw new \Exception('preference "'.self::DATE_TIME_FORMAT.'" should be a string');
			}
			$this->setDateTimeFormat($preferences[self::DATE_TIME_FORMAT]);
		}
		
		// only updated values
		if (array_key_exists(self::ONLY_UPDATED_VALUES, $preferences)) {
			if (!is_bool($preferences[self::ONLY_UPDATED_VALUES])) {
				throw new \Exception('preference "'.self::ONLY_UPDATED_VALUES.'" should be a boolean');
			}
			$this->setExportOnlyUpdatedValues($preferences[self::ONLY_UPDATED_VALUES]);
		}
		
		// preoperties filters
		if (array_key_exists(self::PROPERTIES_FILTERS, $preferences)) {
			if (!is_array($preferences[self::PROPERTIES_FILTERS])) {
				throw new \Exception('preference "'.self::PROPERTIES_FILTERS.'" should be an array');
			}
			$this->resetPropertiesFilters();
			foreach ($preferences[self::PROPERTIES_FILTERS] as $modelName => $properties) {
				$this->setPropertiesFilter($properties, $modelName);
			}
		}
		
		// flatten values
		if (array_key_exists(self::FLATTEN_VALUES, $preferences)) {
			if (!is_bool($preferences[self::FLATTEN_VALUES])) {
				throw new \Exception('preference "'.self::FLATTEN_VALUES.'" should be a boolean');
			}
			$this->setFlattenValues($preferences[self::FLATTEN_VALUES]);
		}
		
		// main foreign objects
		if (array_key_exists(self::MAIN_FOREIGN_OBJECTS, $preferences)) {
			if (!is_bool($preferences[self::MAIN_FOREIGN_OBJECTS])) {
				throw new \Exception('preference "'.self::MAIN_FOREIGN_OBJECTS.'" should be a boolean');
			}
			$this->setExportMainForeignObjects($preferences[self::MAIN_FOREIGN_OBJECTS]);
		}
		
		// flag values as updated
		if (array_key_exists(self::FLAG_VALUES_AS_UPDATED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_VALUES_AS_UPDATED])) {
				throw new \Exception('preference "'.self::FLAG_VALUES_AS_UPDATED.'" should be a boolean');
			}
			$this->setFlagValuesAsUpdated($preferences[self::FLAG_VALUES_AS_UPDATED]);
		}
		
		// flag Object as updated
		if (array_key_exists(self::FLAG_OBJECT_AS_LOADED, $preferences)) {
			if (!is_bool($preferences[self::FLAG_OBJECT_AS_LOADED])) {
				throw new \Exception('preference "'.self::FLAG_OBJECT_AS_LOADED.'" should be a boolean');
			}
			$this->setFlagObjectAsLoaded($preferences[self::FLAG_OBJECT_AS_LOADED]);
		}
		
		// merge type
		if (array_key_exists(self::MERGE_TYPE, $preferences)) {
			$this->setMergeType($preferences[self::MERGE_TYPE]);
		}
	}
	
}
