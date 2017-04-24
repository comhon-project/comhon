<?php
namespace comhon\interfacer;

use comhon\object\Object;
use comhon\model\Model;
use comhon\model\singleton\ModelManager;
use comhon\model\MainModel;

abstract class Interfacer {
	
	const PRIVATE                = 'private';
	const SERIAL_CONTEXT         = 'serialContext';
	const DATE_TIME_ZONE         = 'dateTimeZone';
	const DATE_TIME_FORMAT       = 'dateTimeFormat';
	const ONLY_UPDATED_VALUES    = 'updatedValueOnly';
	const PROPERTIES_FILTERS     = 'propertiesFilters';
	const FLATTEN_VALUES         = 'flattenValues';
	const MAIN_FOREIGN_OBJECTS   = 'mainForeignObjects';
	const FLAG_VALUES_AS_UPDATED = 'flagValuesAsUpdated';
	const MERGE_TYPE             = 'mergeType';
	
	const MERGE     = 1;
	const OVERWRITE = 2;
	const NO_MERGE  = 3;
	
	const __UNLOAD__ = '__UNLOAD__';
	const INHERITANCE_KEY = '__inheritance__';
	const COMPLEX_ID_KEY = 'id';
	
	private static $sAllowedMergeTypes = [
		self::MERGE,
		self::OVERWRITE,
		self::NO_MERGE
	];
	
	private $mPrivate             = false;
	private $mSerialContext       = false;
	private $mDateTimeZone        = null;
	private $mDateTimeFormat      = 'c';
	private $mUpdatedValueOnly    = false;
	private $mPropertiesFilters   = [];
	private $mFlattenValues       = false;
	private $mFlagValuesAsUpdated = false;
	private $mMergeType           = self::MERGE;
	
	protected $mMainForeignObjects  = null;
	protected $mMainForeignIds      = null;
	
	public function __construct() {
		$this->mDateTimeZone = new \DateTimeZone(date_default_timezone_get());
	}
	
	/**
	 * verify if private properties have to be interfaced
	 * @return boolean
	 */
	public function interfacePrivateProperties() {
		return $this->mPrivate;
	}
	
	/**
	 * define if private properties have to be interfaced
	 * @param boolean $pBoolean
	 */
	public function setInterfacePrivateProperties($pBoolean) {
		$this->mPrivate = $pBoolean;
	}
	
	/**
	 * verify if interfacer is used in serial context (serialization / deserialization)
	 * @return boolean
	 */
	public function isSerialContext() {
		return $this->mSerialContext;
	}
	
	/**
	 * define if interfacer is used in serial context (serialization / deserialization)
	 * @param boolean $pBoolean if true, use properties serialization name, and ignore aggregations
	 */
	public function setSerialContext($pBoolean) {
		$this->mSerialContext = $pBoolean;
	}
	
	/**
	 * get date time zone
	 * @return \DateTimeZone
	 */
	public function getDateTimeZone() {
		return $this->mDateTimeZone;
	}
	
	/**
	 * set date time zone
	 * @param string $pTimeZone
	 */
	public function setDateTimeZone($pTimeZone) {
		$this->mDateTimeZone = new \DateTimeZone($pTimeZone);
	}
	
	/**
	 * get date time format
	 * @return string
	 */
	public function getDateTimeFormat() {
		return $this->mDateTimeFormat;
	}
	
	/**
	 * set date time format
	 * @param string $pDateTimeFormat
	 */
	public function setDateTimeFormat($pDateTimeFormat) {
		$this->mDateTimeFormat = $pDateTimeFormat;
	}
	
	/**
	 * verify if has to export only updated values
	 * @return boolean
	 */
	public function hasToExportOnlyUpdatedValues() {
		return $this->mUpdatedValueOnly;
	}
	
	/**
	 * define if has to export only updated values
	 * @param boolean $pBoolean
	 */
	public function setExportOnlyUpdatedValues($pBoolean) {
		$this->mUpdatedValueOnly = $pBoolean;
	}
	
	/**
	 * verify if has properties filter for specified model
	 * @param string $pModelName
	 * @return boolean $pBoolean
	 */
	public function hasPropertiesFilter($pModelName) {
		return array_key_exists($pModelName, $this->mPropertiesFilters);
	}
	
	/**
	 * get properties filter for specified model (properties names are stored in array keys)
	 * @param string $pModelName
	 * @return array|null return null if filter doesn't exist for specified model
	 */
	public function getPropertiesFilter($pModelName) {
		return array_key_exists($pModelName, $this->mPropertiesFilters)
		? $this->mPropertiesFilters[$pModelName]
		: null;
	}
	
	/**
	 * reset properties filter
	 */
	public function resetPropertiesFilters() {
		$this->mPropertiesFilters = [];
	}
	
	/**
	 * set properties filter for specified model
	 * @param string[] $pPropertiesNames
	 * @param string $pModelName
	 */
	public function setPropertiesFilter($pPropertiesNames, $pModelName) {
		$this->mPropertiesFilters[$pModelName] = array_flip($pPropertiesNames);
		$lModel = ModelManager::getInstance()->getInstanceModel($pModelName);
		foreach ($lModel->getIdProperties()as $lPropertyName => $lProperty) {
			$this->mPropertiesFilters[$pModelName][$lPropertyName] = null;
		}
	}
	
	/**
	 * 
	 * @param boolean $pBoolean
	 */
	public function setFlattenValues($pBoolean) {
		$this->mFlattenValues = $pBoolean;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function hasToFlattenValues() {
		return $this->mFlattenValues;
	}
	
	/**
	 * verify if has to export main foreign objects
	 * @return boolean
	 */
	public function hasToExportMainForeignObjects() {
		return !is_null($this->mMainForeignObjects);
	}
	
	/**
	 * define if has to export main foreign objects
	 * @param boolean $pBoolean
	 */
	public function setExportMainForeignObjects($pBoolean) {
		$this->mMainForeignObjects = $pBoolean ? $this->createNode('objects') : null;
		$this->mMainForeignIds = $pBoolean ? [] : null;
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function addMainForeignObject($pNode, $pNodeId, Model $pModel) {
		if (!is_null($this->mMainForeignObjects)) {
			$lModelName = $pModel->getName();
			if (!$this->hasValue($this->mMainForeignObjects, $lModelName, true)) {
				$this->setValue($this->mMainForeignObjects, $this->createNode($lModelName), $lModelName);
			}
			$this->setValue($this->getValue($this->mMainForeignObjects, $lModelName, true), $pNode, $pNodeId);
		}
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function removeMainForeignObject($pNodeId, Model $pModel) {
		if (!is_null($this->mMainForeignObjects)) {
			$lModelName = $pModel->getName();
			if ($this->hasValue($this->mMainForeignObjects, $lModelName, true)) {
				$this->deleteValue($this->getValue($this->mMainForeignObjects, $lModelName, true), $pNodeId, true);
			}
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function getMainForeignObjects() {
		return $this->mMainForeignObjects;
	}
	
	/**
	 *
	 * @return array
	 */
	public function hasMainForeignObject($pModelName, $pId) {
		return !is_null($this->mMainForeignObjects)
			&& $this->hasValue($this->mMainForeignObjects, $pModelName, true)
			&& $this->hasValue($this->getValue($this->mMainForeignObjects, $pModelName, true), $pId, true);
	}
	
	/**
	 *
	 * @param boolean $pBoolean
	 */
	public function setFlagValuesAsUpdated($pBoolean) {
		$this->mFlagValuesAsUpdated = $pBoolean;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function hasToFlagValuesAsUpdated() {
		return $this->mFlagValuesAsUpdated;
	}
	
	/**
	 *
	 * @param integer $pMergeType
	 */
	public function setMergeType($pMergeType) {
		if (!in_array($pMergeType, self::$sAllowedMergeTypes)) {
			throw new \Exception("merge type '$pMergeType' not allowed");
		}
		$this->mMergeType = $pMergeType;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getMergeType() {
		return $this->mMergeType;
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	abstract public function &getValue(&$pNode, $pPropertyName, $pAsNode = false);
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return boolean
	 */
	abstract public function hasValue($pNode, $pPropertyName, $pAsNode = false);
	
	/**
	 *
	 * @param mixed $pNode
	 * @return mixed
	 */
	abstract public function getTraversableNode($pNode);
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $pNode
	 * @return mixed
	 */
	abstract public function isComplexInterfacedId($pValue);
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param mixed $pValue
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	abstract public function setValue(&$pNode, $pValue, $pName = null, $pAsNode = false);
	
	/**
	 *
	 * @param mixed $pNode
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	abstract public function deleteValue(&$pNode, $pName, $pAsNode = false);
	
	/**
	 *
	 * @param mixed $pNode
	 * @param mixed $pValue
	 * @param string $pName
	 * @return mixed
	 */
	abstract public function addValue(&$pNode, $pValue, $pName = null);
	
	/**
	 * @param string $pName
	 * return mixed
	 */
	abstract public function createNode($pName = null);
	
	/**
	 * @param string $pName
	 * @return mixed
	 */
	abstract public function createNodeArray($pName = null);
	
	/**
	 * serialize given node
	 * @param mixed $pNode
	 * @return string
	 */
	abstract public function serialize($pNode);
	
	/**
	 * flatten value (transform object/array to string)
	 * @param mixed $pNode
	 * @param string $pName
	 */
	abstract public function flattenNode(&$pNode, $pName);
	
	/**
	 * replace value
	 * @param mixed $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	abstract public function replaceValue(&$pNode, $pName, $pValue);
	
	/**
	 * verify node type according interfacer type
	 * @param mixed $pNode
	 * @return boolean
	 */
	abstract public function verifyNode($pNode);
	
	/**
	 * verify if interfaced object has typed scalar values (int, float, string...).
	 * @return boolean
	 */
	public function hasScalarTypedValues() {
		return !($this instanceof NoScalarTypedInterfacer);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param array $pPreferences
	 */
	public function export(Object $pObject, $pPreferences = []) {
		$this->setPreferences($pPreferences);
		return $pObject->export($this);
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param MainModel $pModel
	 * @param array $pPreferences
	 */
	public function import($pNode, MainModel $pModel, array $pPreferences = []) {
		$this->setPreferences($pPreferences);
		return $pModel->import($pNode, $this);
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @return boolean
	 */
	public function setPreferences(array $pPreferences) {
		// private
		if (array_key_exists(self::PRIVATE, $pPreferences)) {
			if (!is_bool($pPreferences[self::PRIVATE])) {
				throw new \Exception('preference "'.self::PRIVATE.'" should be a boolean');
			}
			$this->setInterfacePrivateProperties($pPreferences[self::PRIVATE]);
		}
		
		// serial context
		if (array_key_exists(self::SERIAL_CONTEXT, $pPreferences)) {
			if (!is_bool($pPreferences[self::SERIAL_CONTEXT])) {
				throw new \Exception('preference "'.self::SERIAL_CONTEXT.'" should be a boolean');
			}
			$this->setSerialContext($pPreferences[self::SERIAL_CONTEXT]);
		}
		
		// date time zone
		if (array_key_exists(self::DATE_TIME_ZONE, $pPreferences)) {
			if (!is_string($pPreferences[self::DATE_TIME_ZONE])) {
				throw new \Exception('preference "'.self::DATE_TIME_ZONE.'" should be a string');
			}
			$this->setDateTimeZone($pPreferences[self::DATE_TIME_ZONE]);
		}
		
		// date time format
		if (array_key_exists(self::DATE_TIME_FORMAT, $pPreferences)) {
			if (!is_string($pPreferences[self::DATE_TIME_FORMAT])) {
				throw new \Exception('preference "'.self::DATE_TIME_FORMAT.'" should be a string');
			}
			$this->setDateTimeFormat($pPreferences[self::DATE_TIME_FORMAT]);
		}
		
		// only updated values
		if (array_key_exists(self::ONLY_UPDATED_VALUES, $pPreferences)) {
			if (!is_bool($pPreferences[self::ONLY_UPDATED_VALUES])) {
				throw new \Exception('preference "'.self::ONLY_UPDATED_VALUES.'" should be a boolean');
			}
			$this->setExportOnlyUpdatedValues($pPreferences[self::ONLY_UPDATED_VALUES]);
		}
		
		// preoperties filters
		if (array_key_exists(self::PROPERTIES_FILTERS, $pPreferences)) {
			if (!is_array($pPreferences[self::PROPERTIES_FILTERS])) {
				throw new \Exception('preference "'.self::PROPERTIES_FILTERS.'" should be an array');
			}
			foreach ($pPreferences[self::PROPERTIES_FILTERS] as $lModelName => $lProperties) {
				$this->setPropertiesFilter($lProperties, $lModelName);
			}
		}
		
		// flatten values
		if (array_key_exists(self::FLATTEN_VALUES, $pPreferences)) {
			if (!is_bool($pPreferences[self::FLATTEN_VALUES])) {
				throw new \Exception('preference "'.self::FLATTEN_VALUES.'" should be a boolean');
			}
			$this->setFlattenValues($pPreferences[self::FLATTEN_VALUES]);
		}
		
		// main foreign objects
		if (array_key_exists(self::MAIN_FOREIGN_OBJECTS, $pPreferences)) {
			if (!is_bool($pPreferences[self::MAIN_FOREIGN_OBJECTS])) {
				throw new \Exception('preference "'.self::MAIN_FOREIGN_OBJECTS.'" should be a boolean');
			}
			$this->setExportMainForeignObjects($pPreferences[self::MAIN_FOREIGN_OBJECTS]);
		} else if (!is_null($this->mMainForeignObjects)) {
			$this->mMainForeignObjects = $this->createNode('objects');
			$this->mMainForeignIds = [];
		}
		
		// flag values as updated
		if (array_key_exists(self::FLAG_VALUES_AS_UPDATED, $pPreferences)) {
			if (!is_bool($pPreferences[self::FLAG_VALUES_AS_UPDATED])) {
				throw new \Exception('preference "'.self::FLAG_VALUES_AS_UPDATED.'" should be a boolean');
			}
			$this->setFlagValuesAsUpdated($pPreferences[self::FLAG_VALUES_AS_UPDATED]);
		}
		
		// merge type
		if (array_key_exists(self::MERGE_TYPE, $pPreferences)) {
			$this->setMergeType($pPreferences[self::MERGE_TYPE]);
		}
	}
	
}
