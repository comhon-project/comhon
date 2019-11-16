<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object\Collection;

use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\UniqueObject;
use Comhon\Exception\ComhonException;
use Comhon\Model\Model;

class ObjectCollection {
	
	/**
	 * 
	 * @var UniqueObject[]
	 */
	protected $map = [];
	
	/**
	 * get model that will be used to store object with specified model.
	 * returned model may be different than specified model due to shared id
	 *
	 * @param \Comhon\Model\Model $model
	 * @return \Comhon\Model\Model
	 */
	public static function getModelKey(Model $model) {
		return is_null($model->getSharedIdModel()) ? $model : $model->getSharedIdModel();
	}
	
	/**
	 * get comhon object with specified model name (if exists in ObjectCollection)
	 * 
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model with same serialization too
	 * @return \Comhon\Object\UniqueObject|null null if not found
	 */
	public function getObject($id, $modelName, $inlcudeInheritance = true) {
		$object = null;
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$key = self::getModelKey($model)->getName();
		
		if (array_key_exists($key, $this->map) && array_key_exists($id, $this->map[$key])) {
			$objectTemp = $this->map[$key][$id];
			if (
				$objectTemp->getModel() === $model 
				|| ($objectTemp->getModel()->isInheritedFrom($model)) 
				|| ($inlcudeInheritance && $model->isInheritedFrom($objectTemp->getModel()))
			) {
				$object = $objectTemp;
			}
		}
		
		return $object;
	}
	
	/**
	 * verify if comhon object with specified model name and id exists in ObjectCollection
	 * 
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model with same serialization too
	 * @return boolean true if exists
	 */
	public function hasObject($id, $modelName, $inlcudeInheritance = true) {
		return !is_null($this->getObject($id, $modelName, $inlcudeInheritance));
	}
	
	/**
	 * get all comhon objects with specified model name if exists
	 * 
	 * @param string $modelName
	 * @return \Comhon\Object\UniqueObject[]
	 */
	public function getModelObjects($modelName) {
		return array_key_exists($modelName, $this->map) ? $this->map[$modelName] : [];
	}
	
	/**
	 * add comhon object (if not already added)
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param boolean $throwException it true, throw exception if another instance object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(UniqueObject $object, $throwException = true) {
		$success = false;
		
		if (!$object->hasCompleteId() || !$object->getModel()->hasIdProperties()) {
			return $success;
		}
		$id = $object->getId();
		$key = self::getModelKey($object->getModel())->getName();
		
		if (array_key_exists($key, $this->map) && array_key_exists($id, $this->map[$key])) {
			if ($throwException && $this->map[$key][$id] !== $object) {
				throw new ComhonException('different object instance with same id');
			}
		} else {
			$this->map[$key][$id] = $object;
			$success = true;
		}
		
		return $success;
	}
	
	/**
	 * remove comhon object from collection if exists
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param boolean $throwException it true, throw exception if another instance object exists
	 * @throws \Exception
	 * @return boolean true if object is removed
	 */
	public function removeObject(UniqueObject $object, $throwException = true) {
		$success = false;
		
		if (!$object->hasCompleteId() || !$object->getModel()->hasIdProperties()) {
			return $success;
		}
		$id = $object->getId();
		$key = self::getModelKey($object->getModel())->getName();
		
		if (array_key_exists($key, $this->map) && array_key_exists($id, $this->map[$key])) {
			if ($this->map[$key][$id] === $object) {
				unset($this->map[$key][$id]);
				$success = true;
			} elseif ($throwException) {
				throw new ComhonException('different object instance with same id');
			}
		}
		
		return $success;
	}
	
	/**
	 * export all comhon objects in stdClass objets
	 * 
	 * @return array[]
	 */
	public function toStdObject() {
		$array = [];
		$interfacer = new StdObjectInterfacer();
		$interfacer->setPrivateContext(true);
		foreach ($this->map as $modelName => $objectById) {
			$array[$modelName] = [];
			foreach ($objectById as $id => $object) {
				$array[$modelName][$id] = $object->export($interfacer);
			}
		}
		return $array;
	}
	
	/**
	 * reset object collection
	 */
	public function reset() {
		$this->map = [];
	}
	
	/**
	 * export all comhon objects in stdClass objets and json encode them
	 * 
	 * @return string
	 */
	public function toString() {
		return json_encode($this->toStdObject());
	}
}