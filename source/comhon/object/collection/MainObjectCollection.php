<?php
namespace comhon\object\collection;

use comhon\object\Object;
use comhon\model\Model;
use comhon\model\MainModel;
use comhon\model\singleton\ModelManager;

class MainObjectCollection extends ObjectCollection {
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	
	private function __construct() {}
	
	/**
	 * get Object with Model if exists
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @param boolean $pInlcudeInheritance if true, search in extended model with same serialization too
	 * @return Object|null
	 */
	public function getObject($pId, $pModelName, $pInlcudeInheritance = true) {
		$lObject = parent::getObject($pId, $pModelName);
		if (is_null($lObject) && $pInlcudeInheritance) {
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
		$lHasObject = parent::hasObject($pId, $pModelName);
		if (!$lHasObject && $pInlcudeInheritance) {
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
	 * add object with mainModel (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		$lSuccess = parent::addObject($pObject, $pThrowException);
		
		if ($lSuccess) {
			$lId            = $pObject->getId();
			$lSerialization = $pObject->getModel()->getSerializationSettings();
			
			if (!is_null($lSerialization)) {
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
	 * add object with mainModel (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(Object $pObject) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		$lSuccess = parent::removeObject($pObject);
	
		if ($lSuccess) {
			$lId            = $pObject->getId();
			$lSerialization = $pObject->getModel()->getSerializationSettings();
				
			if (!is_null($lSerialization)) {
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
}