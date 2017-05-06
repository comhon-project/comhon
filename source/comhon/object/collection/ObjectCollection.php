<?php
namespace comhon\object\collection;

use comhon\object\Object;
use comhon\model\Model;
use comhon\interfacer\StdObjectInterfacer;
use comhon\model\singleton\ModelManager;

class ObjectCollection {
	
	protected $mMap = [];
	
	/**
	 * get Object with Model if exists
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @param boolean $pInlcudeInheritance if true, search in extended model with same serialization too
	 * @return Object|null
	 */
	public function getObject($pId, $pModelName, $pInlcudeInheritance = true) {
		$lObject = array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName])
			? $this->mMap[$pModelName][$pId]
			: null;
		
		if (is_null($lObject) && $pInlcudeInheritance && ModelManager::getInstance()->hasModel($pModelName)) {
			$lCurrentModel = ModelManager::getInstance()->getInstanceModel($pModelName);
			$lSerialization = $lCurrentModel->getSerializationSettings();
			
			if (!is_null($lSerialization)) {
				$lModelNames = [];
				$lModel = $lCurrentModel->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerializationSettings() === $lSerialization) {
					$lModelNames[] = $lModel->getName();
					if (isset($this->mMap[$lModel->getName()][$pId])) {
						if (in_array($this->mMap[$lModel->getName()][$pId]->getModel()->getName(), $lModelNames)) {
							$lObject = $this->mMap[$lModel->getName()][$pId];
						}
						break;
					}
					$lModel = $lModel->getExtendsModel();
				}
			}
		}
		return $lObject;
	}
	
	/**
	 * verify if Object with specified Model and id exists in ObjectCollection
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @param boolean $pInlcudeInheritance if true, search in extended model with same serialization too
	 * @return boolean true if exists
	 */
	public function hasObject($pId, $pModelName, $pInlcudeInheritance = true) {
		$lHasObject = array_key_exists($pModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pModelName]);
		
		if (!$lHasObject && $pInlcudeInheritance && ModelManager::getInstance()->hasModel($pModelName)) {
			$lCurrentModel = ModelManager::getInstance()->getInstanceModel($pModelName);
			$lSerialization = $lCurrentModel->getSerializationSettings();
			
			if (!is_null($lSerialization)) {
				$lModelNames = [];
				$lModel = $lCurrentModel->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerializationSettings() === $lSerialization) {
					$lModelNames[] = $lModel->getName();
					if (isset($this->mMap[$lModel->getName()][$pId])) {
						$lHasObject = in_array($this->mMap[$lModel->getName()][$pId]->getModel()->getName(), $lModelNames);
						break;
					}
					$lModel = $lModel->getExtendsModel();
				}
			}
		}
		return $lHasObject;
	}
	
	/**
	 * get all Objects with specified Model if exists
	 * @param string $pModelName
	 * @return Object|null
	 */
	public function getModelObjects($pModelName) {
		return array_key_exists($pModelName, $this->mMap) ? $this->mMap[$pModelName] : null;
	}
	
	/**
	 * add object (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		$lSuccess = false;
		
		if ($pObject->hasCompleteId() && $pObject->getModel()->hasIdProperties()) {
			$lModelName = $pObject->getModel()->getName();
			$lId = $pObject->getId();
			if (!array_key_exists($lModelName, $this->mMap)) {
				$this->mMap[$lModelName] = [];
			}
			// if object NOT already added, we can add it
			if(!array_key_exists($lId, $this->mMap[$lModelName])) {
				$this->mMap[$lModelName][$lId] = $pObject;
				$lSuccess = true;
			}
			else if ($pThrowException) {
				throw new \Exception('object already added');
			}
		}
		
		if ($lSuccess) {
			$lSerialization = $pObject->getModel()->getSerializationSettings();
			
			if (!is_null($lSerialization)) {
				$lId    = $pObject->getId();
				$lModel = $pObject->getModel()->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerializationSettings() === $lSerialization) {
					if (isset($this->mMap[$lModel->getName()][$lId])) {
						if ($this->mMap[$lModel->getName()][$lId] !== $pObject) {
							throw new \Exception('extends model already has different object instance with same id');
						}
						break;
					}
					$this->mMap[$lModel->getName()][$lId] = $pObject;
					$lModel = $lModel->getExtendsModel();
				}
			}
		}
		return $lSuccess;
	}
	
	/**
	 * remove object from collection if exists
	 * @param Object $pObject
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(Object $pObject) {
		$lSuccess = false;
		if ($pObject->hasCompleteId() && $this->getObject($pObject->getId(), $pObject->getModel()->getName()) === $pObject) {
			unset($this->mMap[$pObject->getModel()->getName()][$pObject->getId()]);
			$lSuccess = true;
		}
		
		if ($lSuccess) {
			$lSerialization = $pObject->getModel()->getSerializationSettings();
			
			if (!is_null($lSerialization)) {
				$lId    = $pObject->getId();
				$lModel = $pObject->getModel()->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerializationSettings() === $lSerialization) {
					if (!isset($this->mMap[$lModel->getName()][$lId]) || $this->mMap[$lModel->getName()][$lId] !== $pObject) {
						throw new \Exception('extends model doesn\'t have object or has different object instance with same id');
					}
					unset($this->mMap[$lModel->getName()][$lId]);
					$lModel = $lModel->getExtendsModel();
				}
			}
		}
		return $lSuccess;
	}
	
	public function toStdObject() {
		$lArray = [];
		$lInterfacer = new StdObjectInterfacer();
		$lInterfacer->setPrivateContext(true);
		foreach ($this->mMap as $lModelName => $lObjectById) {
			$lArray[$lModelName] = [];
			foreach ($lObjectById as $lId => $lObject) {
				$lArray[$lModelName][$lId] = $lObject->export($lInterfacer);
			}
		}
		return $lArray;
	}
	
	public function toString() {
		return json_encode($this->toStdObject());
	}
}