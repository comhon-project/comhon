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

use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Exception\Model\CastComhonObjectException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Model\Model;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Value\UnexpectedArrayException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Object\MissingRequiredValueException;
use Comhon\Exception\Object\ConflictValuesException;
use Comhon\Exception\Object\DependsValuesException;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ArgumentException;
use Comhon\Model\ModelComplex;
use Comhon\Exception\Object\AbstractObjectException;

abstract class UniqueObject extends AbstractComhonObject {
	
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
	 * @see \Comhon\Object\AbstractComhonObject::reset()
	 * 
	 * @param boolean $resetId if false and object has id, object is reset except id
	 */
	final public function reset($resetId = true) {
		$values = [];
		if ($this->getModel()->hasIdProperties())  {
			if ($resetId) {
				if ($this->getModel()->isMain()) {
					MainObjectCollection::getInstance()->removeObject($this, false);
				}
			} else {
				foreach ($this->getModel()->getIdProperties() as $property) {
					if ($this->issetValue($property->getName())) {
						$values[$property->getName()] = $this->getValue($property->getName());
					}
				}
			}
		}
		foreach ($this->getModel()->getPropertiesWithDefaultValues() as $property) {
			$values[$property->getName()] = $property->getDefaultValue();
		}
		$this->_reset();
		$this->_setValues($values, false);
	}
	
	/**
	 * set id (model associated to comhon object must have at least one id property)
	 *
	 * @param mixed $id
	 * @param boolean $flagAsUpdated
	 * @throws \Exception
	 */
	final public function setId($id, $flagAsUpdated = true) {
		if (!$this->getModel()->hasIdProperties()) {
			throw new ComhonException("cannot set id. model {$this->getModel()->getName()} doesn't have id property");
		}
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
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::setValue()
	 */
	final public function setValue($name, $value, $flagAsUpdated = true) {
		try {
			$property = $this->getModel()->getProperty($name, true);
			$property->validate($value);
			if ($property->isAggregation()) {
				$flagAsUpdated = false;
			}
		} catch (NotSatisfiedRestrictionException $e) {
			throw new NotSatisfiedRestrictionException($e->getValue(), $e->getRestriction());
		} catch (UnexpectedArrayException $e) {
			throw new UnexpectedArrayException($value, $e->getModelArray(), $e->getDepth());
		} catch (UnexpectedValueTypeException $e) {
			throw new UnexpectedValueTypeException($value, $e->getExpectedType());
		}
		// previous exception catched is thrown again to simplify trace stack
		
		parent::setValue($name, $value, $flagAsUpdated);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::initValue()
	 */
	final public function initValue($name, $isLoaded = true, $flagAsUpdated = true) {
		$this->setValue($name, $this->getInstanceValue($name, $isLoaded), $flagAsUpdated);
		return $this->getValue($name);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                        Values Getters                                         |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * get instance value
	 *
	 * may only be applied on property with complex model (model instance of \Comhon\Model\ModelComplex)
	 *
	 * @param string $name
	 * @param boolean $isLoaded
	 * @return UniqueObject|ComhonArray
	 */
	final public function getInstanceValue($name, $isLoaded = true) {
		$propertyModel = $this->getModel()->getProperty($name, true)->getModel();
		if (!($propertyModel instanceof ModelComplex)) {
			throw new ComhonException("property '$name' has a simple model and can't have instance value");
		}
		return $propertyModel->getObjectInstance($isLoaded);
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
		return Model::encodeId($values);
	}
	
	/**
	 * verify if id value(s) is(are) set
	 *
	 * @return boolean true if all id values are set or if associated model doesn't have id properties
	 */
	final public function hasCompleteId() {
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			if(is_null($this->getValue($propertyName)) || $this->getValue($propertyName) === '') {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * verify if at least one id value is set
	 *
	 * @return boolean true if no one id value is set or if model doesn't have id properties
	 */
	final public function hasEmptyId() {
		foreach ($this->getModel()->getIdProperties() as $propertyName => $property) {
			if(!is_null($this->getValue($propertyName))) {
				return false;
			}
		}
		return true;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                      ComhonObject Status                                      |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::isUpdated()
	 */
	final public function isUpdated() {
		if (!$this->isFlaggedAsUpdated()) {
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
		return $this->isFlaggedAsUpdated();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::isIdUpdated()
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
	 * @see \Comhon\Object\AbstractComhonObject::isUpdatedValue()
	 */
	final public function isUpdatedValue($name) {
		if ($this->getModel()->hasProperty($name)) {
			if (array_key_exists($name, $this->getUpdatedValues())) {
				return true;
			} else if ($this->hasValue($name)) {
				if ($this->getValue($name) instanceof AbstractComhonObject) {
					return $this->getModel()->getProperty($name)->isForeign()
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
	 * @see \Comhon\Object\AbstractComhonObject::resetUpdatedStatus()
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
	 * @see \Comhon\Object\AbstractComhonObject::_resetUpdatedStatusRecursive()
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
				if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof AbstractComhonObject)) {
					$this->getValue($propertyName)->_resetUpdatedStatusRecursive($objectHashMap);
				}
			} else if ($this->hasValue($propertyName) && ($this->getValue($propertyName) instanceof ComhonArray)) {
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
	
	/**
	 * validate comhon object.
	 * 
	 * validation concern only required properties, conflicts, dependencies.
	 * throw exception if comhon object is not valid.
	 */
	final public function validate() {
		foreach ($this->getModel()->getRequiredProperties() as $name => $property) {
			if (!$this->hasValue($name)) {
				throw new MissingRequiredValueException($this, $name);
			}
		}
		foreach ($this->getModel()->getConflicts() as $name => $properties) {
			if ($this->hasValue($name)) {
				foreach ($properties as $propertyName) {
					if ($this->hasValue($propertyName)) {
						throw new ConflictValuesException($this->getModel(), [$name, $propertyName]);
					}
				}
			}
		}
		foreach ($this->getModel()->getDependsProperties() as $name => $property) {
			if ($this->hasValue($name)) {
				foreach ($property->getDependencies() as $propertyName) {
					if (!$this->hasValue($propertyName)) {
						throw new DependsValuesException($name, $propertyName);
					}
				}
			}
		}
	}
	
	/**
	 * verify if comhon object is valid.
	 * 
	 * validation concern only required properties, conflicts, dependencies.
	 * 
	 * @return boolean
	 */
	final public function isValid() {
		try {
			$this->validate();
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                      Model - Properties                                       |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * verify if current object model is same as given model or is inherited from given model
	 * 
	 * @param string|\Comhon\Model\Model $model model name or model instance
	 * @return boolean
	 */
	final public function isA($model) {
		if (is_string($model)) {
			$model = ModelManager::getInstance()->getInstanceModel($model);
		} elseif (!($model instanceof Model)) {
			throw new ArgumentException($model, ['string', Model::class], 1);
		}
		return $this->getModel() === $model || $this->getModel()->isInheritedFrom($model);
	}
	
	/**
	 * cast comhon object
	 *
	 * update current model to specified model.
	 * new model must inherit from current model otherwise an exception is thrown
	 *
	 * @param \Comhon\Model\Model $model
	 * @throws \Comhon\Exception\Model\CastComhonObjectException
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
			if (is_null($object) || $object === $this) {
				$addObject = true;
				if (MainObjectCollection::getInstance()->hasObject($this->getId(), $model->getName(), false)) {
					throw new ComhonException("Cannot cast object to '{$model->getName()}'. ComhonObject with id '{$this->getId()}' and model '{$model->getName()}' already exists in MainObjectCollection");
				}
			}
		}
		try {
			if ($this->getModel()->isMain() && $addObject) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			$originalModel = $this->getModel();
			$this->_setModel($model);

			if ($this->isLoaded() && $this->getModel()->isAbstract()) {
				throw new AbstractObjectException($this);
			}
			if ($this->getModel()->isMain() && $addObject) {
				MainObjectCollection::getInstance()->addObject($this);
			}
		} catch (\Exception $e) {
			$this->_setModel($originalModel);
			throw $e;
		}
		$this->isCasted = true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::getComhonClass()
	 */
	final public function getComhonClass() {
		return UniqueObject::class . "({$this->getModel()->getName()})";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::_hasToUpdateMainObjectCollection()
	 */
	protected function _hasToUpdateMainObjectCollection($propertyName) {
		return $this->getModel()->isMain() && $this->getModel()->hasIdProperty($propertyName);
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                Serialization / Deserialization                                |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	
	
	/**
	 * load (deserialize) comhon object using model serialization
	 *
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object already exists and is already loaded, force to reload object
	 * @throws \Exception
	 * @return boolean true if success
	 */
	public function load($propertiesFilter = null, $forceLoad = false) {
		$success = false;
		if (is_null($serialization = $this->getModel()->getSerialization())) {
			throw new ComhonException("object with model {$this->getModel()->getName()} doesn't have serialization");
		}
		if (!$this->isLoaded() || $forceLoad) {
			$success = $serialization->getSerializationUnit()->loadObject($this, $propertiesFilter);
		}
		return $success;
	}
	
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
			throw new SerializationException("object with model {$this->getModel()->getName()} doesn\'t have serialization");
		}
		return $this->getModel()->getSerialization()->getSerializationUnit()->saveObject($this, $operation);
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
			throw new SerializationException("object with model {$this->getModel()->getName()} doesn\'t have serialization");
		}
		return $this->getModel()->getSerialization()->getSerializationUnit()->deleteObject($this);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Object\AbstractComhonObject::loadValue()
	 */
	final public function loadValue($name, $propertiesFilter = null, $forceLoad = false) {
		$property = $this->getModel()->getProperty($name, true);
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
		return $this->getModel()->getProperty($name, true)->loadAggregationIds($this->getValue($name), $this, $forceLoad);
	}
	
}
