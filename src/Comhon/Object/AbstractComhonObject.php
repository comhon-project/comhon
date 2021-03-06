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
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Model\ModelComhonObject;
use Comhon\Model\ModelContainer;
use Comhon\Model\Model;
use Comhon\Exception\Object\AbstractObjectException;

abstract class AbstractComhonObject {

	/**
	 * @var  \Comhon\Model\ModelComhonObject|\Comhon\Model\Model|\Comhon\Model\ModelArray
	 * model associated to comhon object
	 */
	private $model;
	
	/**
	 * @var mixed[] all object values
	 */
	private $values = [];
	
	/**
	 * @var boolean determine if comhon object is loaded
	 */
	private $isLoaded = false;
	
	/**
	 * @var boolean[] references all updated values
	 *     element value is false if set or replaced value
	 *     element value is true if deleted value
	 */
	private $updatedValues = [];
	
	/**
	 * @var boolean determine if object is flagged as updated
	 *     warning! if false, that not necessarily means object is not updated
	 *     actually a sub-object contained in current object may be updated
	 */
	private $isUpdated = false;
	
	/**
	 * affect model to comhon object
	 * 
	 * @param \Comhon\Model\ModelComhonObject $model
	 * @throws \Exception
	 */
	final protected function _affectModel(ModelComhonObject $model) {
		if (!is_null($this->model)) {
			throw new ComhonException('object already initialized');
		}
		$this->model = $model;
	}
	
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Setters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * set specified value
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @param boolean $flagAsUpdated if true, flag value as updated
	 */
	public function setValue($name, $value, $flagAsUpdated = true) {
		if ($this->_hasToUpdateMainObjectCollection($name)) {
			if ($this->hasCompleteId() && MainObjectCollection::getInstance()->getObject($this->getId(), $this->model->getName()) === $this) {
				MainObjectCollection::getInstance()->removeObject($this, false);
			}
			$this->values[$name] = $value;
			MainObjectCollection::getInstance()->addObject($this, false);
		} else {
			$this->values[$name] = $value;
		}
		if ($flagAsUpdated) {
			$this->updatedValues[$name] = null;
			$this->isUpdated = true;
		} elseif (array_key_exists($name, $this->updatedValues)) {
			unset($this->updatedValues[$name]);
			if (empty($this->updatedValues)) {
				$this->isUpdated = false;
			}
		}
	}
	
	/**
	 * add value at the end of array self::$values
	 * 
	 * @param mixed $value
	 * @param boolean $flagAsUpdated
	 */
	final protected function _pushValue($value, $flagAsUpdated) {
		$this->values[] = $value;
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	/**
	 * remove last value from array self::$values
	 * 
	 * @param boolean $flagAsUpdated
	 * @return mixed the last value of array. If array is empty,null will be returned.
	 */
	final protected function _popValue($flagAsUpdated) {
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
		return array_pop($this->values);
	}
	
	/**
	 * add value at the beginning of array self::$values
	 * 
	 * @param mixed $value
	 * @param boolean $flagAsUpdated
	 */
	final protected function _unshiftValue($value, $flagAsUpdated) {
		array_unshift($this->values, $value);
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	/**
	 * remove first value from array self::$values
	 *
	 * @param boolean $flagAsUpdated
	 * @return mixed the first value of array. If array is empty,null will be returned.
	 */
	final protected function _shiftValue($flagAsUpdated) {
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
		return array_shift($this->values);
	}
	
	/**
	 * unset specified value
	 * 
	 * @param string $name
	 * @param boolean $flagAsUpdated
	 */
	public function unsetValue($name, $flagAsUpdated = true) {
		if ($this->hasValue($name)) {
			if ($this->_hasToUpdateMainObjectCollection($name)) {
				MainObjectCollection::getInstance()->removeObject($this);
			}
			unset($this->values[$name]);
			if ($flagAsUpdated) {
				$this->isUpdated = true;
				$this->updatedValues[$name] = null;
			}
		}
	}
	
	/**
	 * instanciate a AbstractComhonObject and add it to values
	 * 
	 * @param string $name may be a property name (if current object is a UniqueObject) or a key (if current object is a ComhonArray)
	 * @param boolean $isLoaded if true, flag value as loaded
	 * @param boolean $flagAsUpdated if true, flag value as updated
	 * @return UniqueObject|ComhonArray
	 */
	abstract public function initValue($name, $isLoaded = true, $flagAsUpdated = true);
	
	/**
	 * set values
	 * 
	 * @param array $values
	 * @param boolean $flagAsUpdated
	 */
	final protected function _setValues(array $values, $flagAsUpdated) {
		$this->values = $values;
		if ($flagAsUpdated) {
			$this->isUpdated = true;
		}
	}
	
	/**
	 * reset values, reset update status and unload object
	 */
	abstract public function reset();
	
	/**
	 * reset values, reset update status and unload object
	 */
	final protected function _reset() {
		$this->values = [];
		$this->isUpdated = false;
		$this->updatedValues = [];
		$this->isLoaded = false;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                        Values Getters                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * 
	 * @param string $name
	 * @return mixed|ComhonArray|UniqueObject|null null if value doesn't exist in values
	 */
	final public function getValue($name) {
		return ($this->hasValue($name)) ? $this->values[$name] : null;
	}
	
	/**
	 * get all comhon object values
	 * 
	 * @return mixed[]
	 */
	final public function getValues() {
		return $this->values;
	}
	
	/**
	 * get associative array that reference names of updated values
	 * 
	 * @return boolean[] each key is a property name of an updated value
	 */
	final public function getUpdatedValues() {
		return $this->updatedValues;
	}
	
	/**
	 * verify if comhon object has specified value set.
	 * if value is set and null, return true.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	final public function hasValue($name) {
		return array_key_exists($name, $this->values);
	}
	
	/**
	 * verify if comhon object has specified value set and not null.
	 * if value is set and null, return false.
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function issetValue($name) {
		return isset($this->values[$name]);
	}
	
	/**
	 * verify if comhon object has all specified values set
	 * 
	 * @param string[] $names
	 * @return boolean
	 */
	final public function hasValues($names) {
		foreach ($names as $name) {
			if (!$this->hasValue($name)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * get object values and array values that contain objects
	 *
	 * @return AbstractComhonObject[]
	 */
	final public function getObjectValues() {
		$values = [];
		foreach ($this->values as $name => $value) {
			if ($value instanceof AbstractComhonObject && $value->getUniqueModel() instanceof Model) {
				$values[$name] = $value;
			}
		}
		return $values;
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       Iterator functions                                      |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * Set the internal pointer of self::$values to its first element
	 */
	final protected function _rewind() {
		reset($this->values);
	}
	
	/**
	 * Return the current element in self::$values
	 * 
	 * @return mixed
	 */
	final protected function _current() {
		return current($this->values);
	}
	
	/**
	 * Fetch a key from self::$values
	 * 
	 * @return mixed
	 */
	final protected function _key() {
		return key($this->values);
	}
	
	/**
	 * Advance the internal array pointer of self::$values
	 */
	final protected function _next() {
		next($this->values);
	}
	
	/**
	 * verify if current internal array pointer of self::$values is valid
	 * 
	 * @return boolean
	 */
	final protected function _valid() {
		return key($this->values) !== null;
	}
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                      ComhonObject Status                                      |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * verify if comhon object is flagged as updated or if at least one value has been updated
	 * 
	 * @return boolean
	 */
	abstract public function isUpdated();
	
	/**
	 * verify if at least one id value has been updated
	 * 
	 * @return boolean
	 */
	abstract public function isIdUpdated();
	
	/**
	 * verify if a value has been updated
	 * 
	 * @param string $name
	 * @return boolean
	 */
	abstract public function isUpdatedValue($name);
	
	/**
	 * verify if object is flagged as updated
	 * 
	 * do not use this function to known if object is updated (use self::isUpdated)
	 * 
	 * @return boolean
	 */
	final public function isFlaggedAsUpdated() {
		return $this->isUpdated;
	}
	
	/**
	 * verify if a value is flagged as updated
	 * 
	 * do not use this function to known if a value is updated (use self::isUpdatedValue)
	 * 
	 * @param string $name
	 * @return boolean
	 */
	final public function isValueFlaggedAsUpdated($name) {
		return array_key_exists($name, $this->updatedValues);
	}
	
	/**
	 * reset updated status
	 * 
	 * @param boolean $recursive if true visit children comhon objects and reset their updated status
	 */
	abstract public function resetUpdatedStatus($recursive = true);
	
	/**
	 * reset updated status of comhon objects recursively
	 * 
	 * @param \Comhon\Object\UniqueObject[] $objectHashMap
	 */
	abstract protected function _resetUpdatedStatusRecursive(&$objectHashMap);
	
	/**
	 * reset updated Status (reset only self::isUpdated and self::updatedValues)
	 */
	final protected function _resetUpdatedStatus() {
		$this->isUpdated = false;
		$this->updatedValues = [];
	}
	
	/**
	 * flag value as updated, only if value is set
	 * 
	 * @param string $name
	 * @return boolean true if success
	 */
	final public function flagValueAsUpdated($name) {
		if ($this->hasValue($name)) {
			$this->isUpdated = true;
			$this->updatedValues[$name] = null;
			return true;
		}
		return false;
	}
	
	/**
	 * verify if comhon object is loaded
	 * 
	 * @return boolean
	 */
	final public function isLoaded() {
		return $this->isLoaded;
	}
	
	/**
	 * set loaded status
	 * 
	 * @param boolean $isLoaded
	 */
	final public function setIsLoaded($isLoaded) {
		if ($isLoaded && !$this->isLoaded) {
			if (($this->getModel() instanceof Model) && $this->getModel()->isAbstract()) {
				throw new AbstractObjectException($this);
			}
		}
		$this->isLoaded = $isLoaded;
	}
	
	/**
	 * validate object.
	 * throw exception if object is not valid.
	 * 
	 */
	abstract  public function validate();
	
	/**
	 * verify if object is valid.
	 * 
	 * @return boolean
	 */
	abstract  public function isValid();
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                      Model - Properties                                       |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * do not use this function, it's only used for cast
	 * 
	 * @param \Comhon\Model\ModelComhonObject $model
	 */
	final protected function _updateModel(ModelComhonObject $model) {
		$this->model = $model;
	}
	
	/**
	 * get model associated to comhon object
	 *
	 * @return \Comhon\Model\Model|\Comhon\Model\ModelArray
	 */
	final public function getModel() {
		return $this->model;
	}
	
	/**
	 * verify if main object collection has to be updated if value is updated
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	abstract protected function _hasToUpdateMainObjectCollection($propertyName);
	
	/**
	 * get unique contained model
	 *
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getUniqueModel() {
		return $this->getModel();
	}
	
	/**
	 * get current comhon object class and its model name
	 * 
	 * @return string
	 */
	abstract public function getComhonClass();
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                Serialization / Deserialization                                |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * load specified value
	 * 
	 * value must be set and must be a comhon object with serialization
	 * 
	 * @param string $name
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	abstract public function loadValue($name, $propertiesFilter = null, $forceLoad = false);
	
	/***********************************************************************************************\
	|                                                                                               |
	|                                       export / import                                         |
	|                                                                                               |
	\***********************************************************************************************/
	
	/**
	 * export comhon object according specified interfacer
	 * 
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	final public function export(Interfacer $interfacer) {
		try {
			return $this->model->export($this, $interfacer);
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
	}
	
	/**
	 * fill comhon object with values of interfaced object
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $forceIsolateElements if object to fill is a comhon array, 
	 *     force isolate each elements of imported array 
	 *     (isolated element doesn't share objects instances with others elements)
	 */
	final public function fill($interfacedObject, Interfacer $interfacer, $forceIsolateElements = true) {
		try {
			$this->model->fillObject($this, $interfacedObject, $interfacer, $forceIsolateElements);
		} catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	 /***********************************************************************************************\
	 |                                                                                               |
	 |                                       toString / debug                                        |
	 |                                                                                               |
	 \***********************************************************************************************/
	
	/**
	 * stringify object
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			$interfacer = new StdObjectInterfacer();
			$interfacer->setPrivateContext(true);
			$interfacer->setVerifyReferences(false);
			$interfacer->setValidate(false);
			return json_encode($interfacer->export($this), JSON_PRETTY_PRINT)."\n";
		} catch (\Exception $e) {
			trigger_error('object can\'t be printed because it is invalid : '.$e->getMessage());
		}
		return '';
	}
	
	/**
	 * output debug infos
	 *
	 * @return array
	 */
	public function __debugInfo() {
		$debugObject = get_object_vars($this);
		if (!array_key_exists('model', $debugObject)) {
			throw new ComhonException('model attribut doesn\'t exist anymore');
		}
		$debugObject['model'] = ($this->model instanceof ModelContainer) ? $this->model->getUniqueModel()->getName() : $this->model->getName();
		return $debugObject;
	}
	
}
