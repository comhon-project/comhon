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

use Comhon\Object\ComhonObject;
use Comhon\Model\Model;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Model\Singleton\ModelManager;

class ObjectCollection {
	
	protected $map = [];
	
	/**
	 * get ComhonObject with Model if exists
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model with same serialization too
	 * @return ComhonObject|null
	 */
	public function getObject($id, $modelName, $inlcudeInheritance = true) {
		$object = array_key_exists($modelName, $this->map) && array_key_exists($id, $this->map[$modelName])
			? $this->map[$modelName][$id]
			: null;
		
		if (is_null($object) && $inlcudeInheritance && ModelManager::getInstance()->hasModel($modelName)) {
			$currentModel = ModelManager::getInstance()->getInstanceModel($modelName);
			$serialization = $currentModel->getSerializationSettings();
			
			if (!is_null($serialization)) {
				$modelNames = [];
				$model = $currentModel->getExtendsModel();
				while (!is_null($model) && $model->getSerializationSettings() === $serialization) {
					$modelNames[] = $model->getName();
					if (isset($this->map[$model->getName()][$id])) {
						if (in_array($this->map[$model->getName()][$id]->getModel()->getName(), $modelNames)) {
							$object = $this->map[$model->getName()][$id];
						}
						break;
					}
					$model = $model->getExtendsModel();
				}
			}
		}
		return $object;
	}
	
	/**
	 * verify if ComhonObject with specified Model and id exists in ObjectCollection
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model with same serialization too
	 * @return boolean true if exists
	 */
	public function hasObject($id, $modelName, $inlcudeInheritance = true) {
		$hasObject = array_key_exists($modelName, $this->map) && array_key_exists($id, $this->map[$modelName]);
		
		if (!$hasObject && $inlcudeInheritance && ModelManager::getInstance()->hasModel($modelName)) {
			$currentModel = ModelManager::getInstance()->getInstanceModel($modelName);
			$serialization = $currentModel->getSerializationSettings();
			
			if (!is_null($serialization)) {
				$modelNames = [];
				$model = $currentModel->getExtendsModel();
				while (!is_null($model) && $model->getSerializationSettings() === $serialization) {
					$modelNames[] = $model->getName();
					if (isset($this->map[$model->getName()][$id])) {
						$hasObject = in_array($this->map[$model->getName()][$id]->getModel()->getName(), $modelNames);
						break;
					}
					$model = $model->getExtendsModel();
				}
			}
		}
		return $hasObject;
	}
	
	/**
	 * get all ComhonObjects with specified Model if exists
	 * @param string $modelName
	 * @return ComhonObject|null
	 */
	public function getModelObjects($modelName) {
		return array_key_exists($modelName, $this->map) ? $this->map[$modelName] : null;
	}
	
	/**
	 * add object (if not already added)
	 * @param ComhonObject $object
	 * @param boolean $throwException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(ComhonObject $object, $throwException = true) {
		$success = false;
		
		if ($object->hasCompleteId() && $object->getModel()->hasIdProperties()) {
			$modelName = $object->getModel()->getName();
			$id = $object->getId();
			if (!array_key_exists($modelName, $this->map)) {
				$this->map[$modelName] = [];
			}
			// if object NOT already added, we can add it
			if(!array_key_exists($id, $this->map[$modelName])) {
				$this->map[$modelName][$id] = $object;
				$success = true;
			}
			else if ($throwException) {
				throw new \Exception('object already added');
			}
		}
		
		if ($success) {
			$serialization = $object->getModel()->getSerializationSettings();
			
			if (!is_null($serialization)) {
				$id    = $object->getId();
				$model = $object->getModel()->getExtendsModel();
				while (!is_null($model) && $model->getSerializationSettings() === $serialization) {
					if (isset($this->map[$model->getName()][$id])) {
						if ($this->map[$model->getName()][$id] !== $object) {
							throw new \Exception('extends model already has different object instance with same id');
						}
						break;
					}
					$this->map[$model->getName()][$id] = $object;
					$model = $model->getExtendsModel();
				}
			}
		}
		return $success;
	}
	
	/**
	 * remove object from collection if exists
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(ComhonObject $object) {
		$success = false;
		if ($object->hasCompleteId() && $this->getObject($object->getId(), $object->getModel()->getName()) === $object) {
			unset($this->map[$object->getModel()->getName()][$object->getId()]);
			$success = true;
		}
		
		if ($success) {
			$serialization = $object->getModel()->getSerializationSettings();
			
			if (!is_null($serialization)) {
				$id    = $object->getId();
				$model = $object->getModel()->getExtendsModel();
				while (!is_null($model) && $model->getSerializationSettings() === $serialization) {
					if (!isset($this->map[$model->getName()][$id]) || $this->map[$model->getName()][$id] !== $object) {
						throw new \Exception('extends model doesn\'t have object or has different object instance with same id');
					}
					unset($this->map[$model->getName()][$id]);
					$model = $model->getExtendsModel();
				}
			}
		}
		return $success;
	}
	
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
	
	public function toString() {
		return json_encode($this->toStdObject());
	}
}