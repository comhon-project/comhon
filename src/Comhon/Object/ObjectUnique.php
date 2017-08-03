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
use Comhon\Exception\CastComhonObjectException;
use Comhon\Object\ObjectArray;
use Comhon\Exception\ComhonException;
use Comhon\Exception\SerializationException;

abstract class ObjectUnique extends ComhonObject {
	
	/**
	 * @var boolean determine if current object has been casted
	 */
	private $isCasted = false;
	
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                        Values Setters                                         |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::reset()
	 */
	final public function reset() {
		if ($this->getModel()->hasIdProperties() && ($this->getModel() instanceof MainModel)) {
			MainObjectCollection::getInstance()->removeObject($this);
		}
		$this->_reset();
		foreach ($this->getModel()->getPropertiesWithDefaultValues() as $property) {
			$this->setValue($property->getName(), $property->getDefaultValue(), false);
		}
	}
	
	/**
	 * set id (model associated to comhon object must have at least one id property)
	 *
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 */
	final public function setId($id, $flagAsUpdated = true) {
		if ($this->getModel()->hasUniqueIdProperty()) {
			$this->setValue($this->getModel()->getUniqueIdProperty()->getName(), $id, $flagAsUpdated);
		}
		else {
			$idValues = $this->getModel()->decodeId($id);
			$i = 0;
			foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
				$this->setValue($propertyName, $idValues[$i], $flagAsUpdated);
				$i++;
			}
		}
	}
	
	/**
	 * reoder values in same order than properties
	 */
	final public function reorderValues() {
		$values = $this->getValues();
		$orderedvalues = [];
		foreach ($this->getModel()->getProperties() as $propertyName => $property) {
			if (array_key_exists($propertyName, $values)) {
				$orderedvalues[$propertyName] = $values[$propertyName];
			}
		}
		$this->_setValues($orderedvalues, false);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                        Values Getters                                         |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * get names of values that have been deleted
	 *
	 * @return string[]
	 */
	final public function getDeletedValues() {
		$deletedValues = [];
		foreach ($this->getUpdatedValues() as $name => $deleted) {
			if ($deleted) {
				$deletedValues[] = $name;
			}
		}
		return $deletedValues;
	}
	
	/**
	 * get id of comhon object
	 *
	 * @return mixed| null if model associated to comhon object doesn't have id properties
	 */
	final public function getId() {
		if ($this->getModel()->hasUniqueIdProperty()) {
			return $this->getValue($this->getModel()->getUniqueIdProperty()->getName());
		}
		$values = [];
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			$values[] = $this->getValue($propertyName);
		}
		return $this->getModel()->encodeId($values);
	}
	
	/**
	 * verify if id value(s) is(are) set
	 *
	 * @return boolean true if all id values are set or if associated model doesn't have id properties
	 */
	final public function hasCompleteId() {
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			if(is_null($this->getValue($propertyName))) {
				return false;
			}
		}
		return true;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                         Object Status                                         |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::isUpdated()
	 */
	final public function isUpdated() {
		if (!$this->isFlagedAsUpdated()) {
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
		return $this->isFlagedAsUpdated();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::isIdUpdated()
	 */
	final public function isIdUpdated() {
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			if ($this->isUpdatedValue($propertyName)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::isUpdatedValue()
	 */
	final public function isUpdatedValue($name) {
		if ($this->hasProperty($name)) {
			if (array_key_exists($name, $this->getUpdatedValues())) {
				return true;
			} else if ($this->hasValue($name)) {
				if ($this->getValue($name) instanceof ComhonObject) {
					return $this->getProperty($name)->isForeign()
					? $this->getValue($name)->isIdUpdated()
					: $this->getValue($name)->isUpdated();
				}
				else if ($this->getValue($name) instanceof ComhonDateTime) {
					return $this->getValue($name)->isUpdated();
				}
			}
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::resetUpdatedStatus()
	 */
	final public function resetUpdatedStatus($recursive = true) {
		if ($recursive) {
			$objectHashMap = [];
			$this->_resetUpdatedStatusRecursive($objectHashMap);
		}else {
			$this->_resetUpdatedStatus();
			foreach ($this->getModel()->getDateTimeProperties() as $propertyName => $property) {
				if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonDateTime)) {
					$this->getValue($propertyName)->resetUpdatedStatus(false);
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::_resetUpdatedStatusRecursive()
	 */
	final protected function _resetUpdatedStatusRecursive(&$objectHashMap) {
		if (array_key_exists(spl_object_hash($this), $objectHashMap)) {
			if ($objectHashMap[spl_object_hash($this)] > 0) {
				trigger_error('Warning loop detected');
				return;
			}
		} else {
			$objectHashMap[spl_object_hash($this)] = 0;
		}
		$objectHashMap[spl_object_hash($this)]++;
		$this->_resetUpdatedStatus();
		foreach ($this->getModel()->getComplexProperties() as $propertyName => $property) {
			if (!$property->isForeign()) {
				if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonObject)) {
					$this->getValue($propertyName)->_resetUpdatedStatusRecursive($objectHashMap);
				}
			} else if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ObjectArray)) {
				$this->getValue($propertyName)->resetUpdatedStatus(false);
			}
		}
		foreach ($this->getModel()->getDateTimeProperties() as $propertyName => $property) {
			if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonDateTime)) {
				$this->getValue($propertyName)->resetUpdatedStatus(false);
			}
		}
		$objectHashMap[spl_object_hash($this)]--;
	}
	
	/**
	 * verify if comhon object has been casted
	 *
	 * @return boolean
	 */
	final public function isCasted() {
		return $this->isCasted;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                      Model - Properties                                       |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * cast comhon object
	 *
	 * update current model to specified model.
	 * new model must inherit from current model otherwise an exception is thrown
	 *
	 * @param \Comhon\Model\Model $model
	 * @throws \Exception
	 * @throws \Comhon\Exception\CastComhonObjectException
	 */
	final public function cast(Model $model) {
		if ($this->getModel() === $model) {
			return;
		}
		if (!$model->isInheritedFrom($this->getModel())) {
			throw new CastComhonObjectException($model, $this->getModel());
		}
		$addObject = false;
		if ($this->hasCompleteId() && $this->getModel()->hasIdProperties()) {
			$object = MainObjectCollection::getInstance()->getObject($this->getId(), $model->getName());
			if ($object === $this) {
				$addObject = true;
				if (MainObjectCollection::getInstance()->hasObject($this->getId(), $model->getName(), false)) {
					throw new ComhonException("Cannot cast object to '{$model->getName()}'. Object with id '{$this->getId()}' and model '{$model->getName()}' already exists in MainModelCollection");
				}
			}
		}
		$this->_setModel($model);
		$this->isCasted = true;
		if (($this->getModel() instanceof MainModel) && $addObject) {
			MainObjectCollection::getInstance()->addObject($this);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::getComhonClass()
	 */
	final public function getComhonClass() {
		return get_class($this) . "({$this->getModel()->getName()})";
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                Serialization / Deserialization                                |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * save (serialize) comhon object using model serialization
	 *
	 * create or update serialized object. some serializations may require id property(ies)
	 *
	 * @param string $operation
	 *     in most cases operation may be ommited, operation is automatically detected
	 *     specify it if object serialization is sqlDatabase and table doesn't have incremental id
	 * @throws \Exception
	 * @return integer count of affected serialized object
	 */
	final public function save($operation = null) {
		if (is_null($this->getModel()->getSerialization())) {
			throw new SerializationException('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->saveObject($this, $operation);
	}
	
	/**
	 * delete serialized object
	 *
	 * model must have id property and id value of comhon object must be set
	 *
	 * @throws \Exception
	 * @return integer count of deleted object
	 */
	final public function delete() {
		if (is_null($this->getModel()->getSerialization())) {
			throw new SerializationException('model doesn\'t have serialization');
		}
		return $this->getModel()->getSerialization()->deleteObject($this);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\ComhonObject::loadValue()
	 */
	final public function loadValue($name, $propertiesFilter = null, $forceLoad = false) {
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
	 *
	 * @param string $name
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	final public function loadAggregationIds($name, $forceLoad = false) {
		if (!$this->hasValue($name)) {
			$this->initValue($name, false);
		}
		return $this->getProperty($name, true)->loadAggregationIds($this->getValue($name), $this, $forceLoad);
	}
	
}
