<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object;

use Comhon\Model\Model;
use Comhon\Model\MainModel;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Exception\CastException;
use Comhon\Object\ObjectArray;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\StdObjectInterfacer;

abstract class ComhonObject {

	private $model;
	private $values        = [];
	private $isLoaded;
	private $updatedValues = [];
	private $isUpdated     = false;
	private $isCasted      = false;
	
	final protected function _affectModel(Model $model) {
		if (!is_null($this->model)) {
			throw new \Exception('object already initialized');
		}
		$this->model = $model;
	}
	
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Setters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * 
	 * @param string $name
	 * @param unknown $value
	 * @param boolean $flagAsUpdated if true, flag value as updated
	 * @param boolean $strict if true, verify value type
	 */
	public final function setValue($name, $value, $flagAsUpdated = true, $strict = true) {
		if ($strict) {
			if ($this instanceof ObjectArray) {
				$this->model->verifElementValue($value);
			} else {
				$property = $this->model->getProperty($name, true);
				$property->isSatisfiable($value, true);
				if (!is_null($value)) {
					$property->getModel()->verifValue($value);
				}
				if ($property->isAggregation()) {
					$flagAsUpdated = false;
				}
			}
		}
		if ($this->model->hasIdProperty($name) && ($this->model instanceof MainModel)) {
			if ($this->hasCompleteId() && MainObjectCollection::getInstance()->getObject($this->getId(), $this->model->getName()) === $this) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			$this->values[$name] = $value;
			MainObjectCollection::getInstance()->addObject($this, false);
		} else {
			$this->values[$name] = $value;
		}
		if ($flagAsUpdated) {
			$this->updatedValues[$name] = false;
			$this->isUpdated = true;
		} else if (array_key_exists($name, $this->updatedValues)) {
			unset($this->updatedValues[$name]);
			if (empty($this->updatedValues)) {
				$this->isUpdated = false;
			}
		}
	}
	
	protected final function _pushValue($value, $flagAsUpdated) {
		$this->values[] = $value;
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	public final function _popValue($flagAsUpdated) {
		array_pop($this->values);
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	public final function _unshiftValue($value, $flagAsUpdated) {
		array_unshift($this->values, $value);
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	public final function _shiftValue($flagAsUpdated) {
		array_shift($this->values);
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	public final function unsetValue($name, $flagAsUpdated = true) {
		if ($this->hasValue($name)) {
			if ($this->model->hasIdProperty($name) && ($this->model instanceof MainModel)) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			unset($this->values[$name]);
			if ($flagAsUpdated) {
				$this->isUpdated = true;
				$this->updatedValues[$name] = true;
			}
		}
	}
	
	/**
	 * instanciate a ComhonObject and add it to values
	 * @param unknown $propertyName
	 * @param string $isLoaded
	 * @param boolean $flagAsUpdated if true, flag value as updated
	 * @return ComhonObject
	 */
	public final function initValue($propertyName, $isLoaded = true, $flagAsUpdated = true) {
		$this->setValue($propertyName, $this->getInstanceValue($propertyName, $isLoaded), $flagAsUpdated);
		return $this->values[$propertyName];
	}
	
	protected final function _setValues(array $values) {
		$this->values = $values;
		$this->isUpdated = true;
	}
	
	/**
	 * reset values and reset update status
	 */
	public final function reset() {
		if ($this->model->hasIdProperties() && ($this->model instanceof MainModel)) {
			MainObjectCollection::getInstance()->removeObject($this);
		}
		$this->values = [];
		$this->isUpdated = false;
		$this->updatedValues = [];
		if (!($this instanceof ObjectArray)) {
			foreach ($this->model->getPropertiesWithDefaultValues() as $property) {
				$this->setValue($property->getName(), $property->getDefaultValue(), false);
			}
		}
	}
	
	public function setId($id, $flagAsUpdated = true) {
		if ($this->model->hasUniqueIdProperty()) {
			$this->setValue($this->model->getUniqueIdProperty()->getName(), $id, $flagAsUpdated);
		}
		else {
			$idValues = $this->model->decodeId($id);
			if (count($this->model->getIdProperties()) !== count($idValues)) {
				throw new \Exception('invalid id : '.$id);
			}
			$i = 0;
			foreach ($this->model->getIdProperties() as $propertyName => $property) {
				$this->setValue($propertyName, $idValues[$i], $flagAsUpdated);
				$i++;
			}
		}
	}
	
	/**
	 * reoder values in same order than properties
	 */
	public final function reorderValues() {
		$values = [];
		foreach ($this->model->getProperties() as $propertyName => $property) {
			if (array_key_exists($propertyName, $this->values)) {
				$values[$propertyName] = $this->values[$propertyName];
			}
		}
		$this->values = $values;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Getters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	public final function getValue($name) {
		return ($this->hasValue($name)) ? $this->values[$name] : null;
	}
	
	public final function getInstanceValue($propertyName, $isLoaded = true) {
		return $this->getProperty($propertyName, true)->getModel()->getObjectInstance($isLoaded);
	}
	
	public final function getValues() {
		return $this->values;
	}
	
	public final function getDeletedValues() {
		$deletedValues = [];
		foreach ($this->updatedValues as $propertyName => $deleted) {
			if ($deleted) {
				$deletedValues[] = $propertyName;
			}
		}
		return $deletedValues;
	}
	
	public final function getUpdatedValues() {
		return $this->updatedValues;
	}
	
	public function getId() {
		if ($this->model->hasUniqueIdProperty()) {
			return $this->getValue($this->model->getUniqueIdProperty()->getName());
		}
		$values = [];
		foreach ($this->model->getIdProperties() as $propertyName => $property) {
			$values[] = $this->getValue($propertyName);
		}
		return $this->model->encodeId($values);
	}
	
	public final function hasCompleteId() {
		foreach ($this->model->getIdProperties() as $propertyName => $property) {
			if(is_null($this->getValue($propertyName))) {
				return false;
			}
		}
		return true;
	}
	
	public final function verifCompleteId() {
		foreach ($this->model->getIdProperties() as $propertyName => $property) {
			if(is_null($this->getValue($propertyName)) || $this->getValue($propertyName) == '') {
				throw new \Excpetion("id is not complete, property '$propertyName' is empty");
			}
		}
	}
	
	public final function hasValue($name) {
		return array_key_exists($name, $this->values);
	}
	
	public final function hasValues($Names) {
		foreach ($Names as $name) {
			if (!$this->hasValue($name)) {
				return false;
			}
		}
		return true;
	}
	
	public final function getValuesCount() {
		return count($this->values);
	}
	
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       Iterator functions                                      |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	protected function _rewind() {
		reset($this->values);
	}
	
	protected function _current() {
		return current($this->values);
	}
	
	protected function _key() {
		return key($this->values);
	}
	
	protected function _next() {
		next($this->values);
	}
	
	protected function _valid() {
		return key($this->values) !== null;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                         Object Status                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isUpdated() {
		if (!$this->isUpdated) {
			foreach ($this->getModel()->getComplexProperties() as $propertyName => $property) {
				if ($this->isUpdatedValue($propertyName)) {
					return true;
				}
			}
			foreach ($this->getModel()->getDateTimeProperties() as $propertyName => $property) {
				if ($this->isUpdatedValue($propertyName)) {
					return true;
				}
			}
		}
		return $this->isUpdated;
	}
	
	/**
	 * verify if at least one value has been updated
	 * @return boolean
	 */
	public function isIdUpdated() {
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			if ($this->isUpdatedValue($propertyName)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * verify if a value has been updated
	 * only works for object that have a model insance of MainModel, otherwise false will be return 
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isUpdatedValue($propertyName) {
		if ($this->hasProperty($propertyName)) {
			if (array_key_exists($propertyName, $this->updatedValues)) {
				return true;
			} else if ($this->hasValue($propertyName)) {
				if ($this->values[$propertyName] instanceof ComhonObject) {
					return $this->getProperty($propertyName)->isForeign()
						? $this->values[$propertyName]->isIdUpdated()
						: $this->values[$propertyName]->isUpdated();
				}
				else if ($this->values[$propertyName] instanceof ComhonDateTime) {
					return $this->values[$propertyName]->isUpdated();
				}
			}
		}
		return false;
	}
	
	/**
	 * verify if object is flaged as updated
	 * do not use this function to known if object is updated (use self::isUpdated)
	 * @return boolean
	 */
	public function isFlagedAsUpdated() {
		return $this->isUpdated;
	}
	
	/**
	 * verify if a value is flaged as updated
	 * do not use this function to known if a value is updated (use self::isUpdatedValue)
	 * @param unknown $propertyName
	 * @return boolean
	 */
	public function isValueFlagedAsUpdated($propertyName) {
		return array_key_exists($propertyName, $this->updatedValues);
	}
	
	public function resetUpdatedStatus($recursive = true) {
		if ($recursive) {
			$objectHashMap = [];
			$this->_resetUpdatedStatusRecursive($objectHashMap);
		}else {
			$this->isUpdated = false;
			$this->updatedValues = [];
			foreach ($this->model->getDateTimeProperties() as $propertyName => $property) {
				if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonDateTime)) {
					$this->getValue($propertyName)->resetUpdatedStatus(false);
				}
			}
		}
	}
	
	protected function _resetUpdatedStatusRecursive(&$objectHashMap) {
		if (array_key_exists(spl_object_hash($this), $objectHashMap)) {
			if ($objectHashMap[spl_object_hash($this)] > 0) {
				trigger_error('Warning loop detected');
				return;
			}
		} else {
			$objectHashMap[spl_object_hash($this)] = 0;
		}
		$objectHashMap[spl_object_hash($this)]++;
		$this->isUpdated = false;
		$this->updatedValues = [];
		foreach ($this->model->getComplexProperties() as $propertyName => $property) {
			if (!$property->isForeign()) {
				if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonObject)) {
					$this->getValue($propertyName)->_resetUpdatedStatusRecursive($objectHashMap);
				}
			} else if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ObjectArray)) {
				$this->getValue($propertyName)->resetUpdatedStatus(false);
			}
		}
		foreach ($this->model->getDateTimeProperties() as $propertyName => $property) {
			if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonDateTime)) {
				$this->getValue($propertyName)->resetUpdatedStatus(false);
			}
		}
		$objectHashMap[spl_object_hash($this)]--;
	}
	
	/**
	 * reset updated Status (reset only self::mIsUpdated and self::mUpdatedValues)
	 */
	protected final function _resetUpdatedStatus() {
		$this->isUpdated = false;
		$this->updatedValues = [];
	}
	
	/**
	 * flag value as updated
	 * @param string $propertyName
	 * @return boolean true if success
	 */
	public function flagValueAsUpdated($propertyName) {
		if ($this->hasValue($propertyName)) {
			$this->isUpdated = true;
			$this->updatedValues[$propertyName] = false;
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public final function isLoaded() {
		return $this->isLoaded;
	}
	
	/**
	 * 
	 * @param boolean $isLoaded
	 */
	public final function setIsLoaded($isLoaded) {
		$this->isLoaded = $isLoaded;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public final function isCasted() {
		return $this->isCasted;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                      Model - Properties                                       |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 *
	 * @return Model
	 */
	public final function getModel() {
		return $this->model;
	}
	
	public final function cast(Model $model) {
		if ($this instanceof ObjectArray) {
			throw new \Exception('object array cannot be casted');
		}
		if ($this->model === $model) {
			return;
		}
		if (!$model->isInheritedFrom($this->model)) {
			throw new CastException($model, $this->model);
		}
		$addObject = false;
		if ($this->hasCompleteId() && $this->getModel()->hasIdProperties()) {
			$object = MainObjectCollection::getInstance()->getObject($this->getId(), $model->getName());
			if ($object === $this) {
				$addObject = true;
				if (MainObjectCollection::getInstance()->hasObject($this->getId(), $model->getName(), false)) {
					throw new \Exception("Cannot cast object to '{$model->getName()}'. Object with id '{$this->getId()}' and model '{$model->getName()}' already exists in MainModelCollection");
				}
			}
		}
		$this->model = $model;
		$this->isCasted = true;
		if ($this->model instanceof MainModel) {
			foreach ($this->model->getAggregations() as $property) {
				if (!array_key_exists($property->getName(), $this->values)) {
					$this->initValue($property->getName(), false, false);
				}
			}
			if ($addObject) {
				MainObjectCollection::getInstance()->addObject($this);
			}
		}
	}
	
	public final function hasProperty($propertyName) {
		return $this->model->hasProperty($propertyName);
	}
	
	public final function getProperties() {
		return $this->model->getProperties();
	}
	
	public final function getPropertiesNames() {
		return $this->model->getPropertiesNames();
	}
	
	public final function getProperty($propertyName, $throwException = false) {
		return $this->model->getProperty($propertyName, $throwException);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                Serialization / Deserialization                                |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 *
	 * @param string $operation specify it only if object serialization is sqlDatabase
	 * @throws \Exception
	 * @return integer
	 */
	public final function save($operation = null) {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->saveObject($this, $operation);
	}
	
	/**
	 *
	 * @throws \Exception
	 * @return integer
	 */
	public final function delete() {
		if (is_null($this->getModel()->getSerialization())) {
			throw new \Exception('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->deleteObject($this);
	}
	
	/**
	 * load value
	 * @param string $name
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($name, $propertiesFilter = null, $forceLoad = false) {
		$property = $this->getProperty($name, true);
		if ($property instanceof AggregationProperty) {
			if (!$this->hasValue($name)) {
				$this->initValue($name, false);
			}
			return $property->loadAggregationValue($this->getValue($name), $this, $propertiesFilter, $forceLoad);
		} else {
			return $property->loadValue($this->getValue($name), $propertiesFilter, $forceLoad);
		}
	}
	
	/**
	 * load aggregation by retrieving only ids
	 * @param string $name
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public final function loadValueIds($name, $forceLoad = false) {
		if (!$this->hasValue($name)) {
			$this->initValue($name, false);
		}
		return $this->getProperty($name, true)->loadValueIds($this->getValue($name), $this, $forceLoad);
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                       export / import                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * 
	 * @param Interfacer $interfacer
	 * @return mixed|null
	 */
	public final function export(Interfacer $interfacer) {
		return $this->model->export($this, $interfacer);
	}
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 */
	public final function fill($interfacedObject, Interfacer $interfacer) {
		$this->model->fillObject($this, $interfacedObject, $interfacer);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       toString / debug                                        |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			$interfacer = new StdObjectInterfacer();
			$interfacer->setPrivateContext(true);
			return json_encode($interfacer->export($this), JSON_PRETTY_PRINT)."\n";
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		return '';
	}
	
	/**
	 *
	 * @return array
	 */
	public function __debugInfo() {
		$debugObject = get_object_vars($this);
		if (!array_key_exists('model', $debugObject)) {
			throw new \Exception('model attribut doesn\'t exist anymore');
		}
		$debugObject['model'] = $this->model->getName();
		return $debugObject;
	}
	
}
