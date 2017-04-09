<?php
namespace comhon\interfacer;

use comhon\object\Object;
use comhon\model\Model;
use comhon\model\singleton\ModelManager;

abstract class Interfacer {
	
	const PRIVATE              = 'private';
	const SERIAL_CONTEXT       = 'serialContext';
	const DATE_TIME_ZONE       = 'dateTimeZone';
	const DATE_TIME_FORMAT     = 'dateTimeFormat';
	const ONLY_UPDATED_VALUES  = 'updatedValueOnly';
	const PROPERTIES_FILTERS   = 'propertiesFilters';
	const FLATTEN_VALUES       = 'flattenValues';
	const MAIN_FOREIGN_OBJECTS = 'mainForeignObjects';
	
	private $mPrivate            = false;
	private $mSerialContext      = false;
	private $mDateTimeZone       = null;
	private $mDateTimeFormat     = 'c';
	private $mUpdatedValueOnly   = false;
	private $mPropertiesFilters  = [];
	private $mFlattenValues      = false;
	private $mMainForeignObjects = null;
	
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
		return is_array($this->mMainForeignObjects);
	}
	
	/**
	 * define if has to export main foreign objects
	 * @param boolean $pBoolean
	 */
	public function setExportMainForeignObjects($pBoolean) {
		$this->mMainForeignObjects = $pBoolean ? [] : null;
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function addMainForeignObject($pNode, $pNodeId, Model $pModel) {
		if (is_array($this->mMainForeignObjects)) {
			$this->mMainForeignObjects[$pModel->getName()][$pNodeId] = $pNode;
		}
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @param string|integer $pNodeId
	 * @param Model $pModel
	 */
	public function removeMainForeignObject($pNodeId, Model $pModel) {
		if (is_array($this->mMainForeignObjects)) {
			unset($this->mMainForeignObjects[$pModel->getName()][$pNodeId]);
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
	abstract protected function _verifyNode($pNode);
	
	
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
	 * @param Model $pModel
	 * @param array $pPreferences
	 */
	public function import($pNode, $pModel, $pPreferences = []) {
		if ($this->_needTransformation($pNode)) {
			$pNode = $this->_transform($pNode);
		}
		$this->_verifyNode($pNode);
		$this->setPreferences($pPreferences);
		$pModel->import($pNode, $this);
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
		} else if (!empty($this->mMainForeignObjects)) {
			$this->mMainForeignObjects = [];
		}
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @return boolean
	 */
	protected function _needTransformation($pNode) {
		return false;
	}
	
	/**
	 *
	 * @param mixed $pNode
	 * @return boolean
	 */
	protected function _transform($pNode) {
		return $pNode;
	}
    
}
