<?php
namespace comhon\object;

use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\singleton\InstanceModel;

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
			$lCurrentModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
			$lSerialization = $lCurrentModel->getSerialization();
			
			if (!is_null($lSerialization)) {
				$lModelNames = [];
				$lModel = $lCurrentModel->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerialization() === $lSerialization) {
					$lModelNames[] = $lModel->getModelName();
					if (isset($this->mMap[$lModel->getModelName()][$pId])) {
						if (in_array($this->mMap[$lModel->getModelName()][$pId]->getModel()->getModelName(), $lModelNames)) {
							$lObject = $this->mMap[$lModel->getModelName()][$pId];
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
			$lCurrentModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
			$lSerialization = $lCurrentModel->getSerialization();
			
			if (!is_null($lSerialization)) {
				$lModelNames = [];
				$lModel = $lCurrentModel->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerialization() === $lSerialization) {
					$lModelNames[] = $lModel->getModelName();
					if (isset($this->mMap[$lModel->getModelName()][$pId])) {
						$lHasObject = in_array($this->mMap[$lModel->getModelName()][$pId]->getModel()->getModelName(), $lModelNames);
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
			$lSerialization = $pObject->getModel()->getSerialization();
			
			if (!is_null($lSerialization)) {
				$lModel = $pObject->getModel()->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerialization() === $lSerialization) {
					if (isset($this->mMap[$lModel->getModelName()][$lId])) {
						if ($this->mMap[$lModel->getModelName()][$lId] !== $pObject) {
							throw new \Exception('extends model already has different object instance with same id');
						}
						break;
					}
					$this->mMap[$lModel->getModelName()][$lId] = $pObject;
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
			$lSerialization = $pObject->getModel()->getSerialization();
				
			if (!is_null($lSerialization)) {
				$lModel = $pObject->getModel()->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerialization() === $lSerialization) {
					if (!isset($this->mMap[$lModel->getModelName()][$lId]) || $this->mMap[$lModel->getModelName()][$lId] !== $pObject) {
						throw new \Exception('extends model doesn\'t have object or has different object instance with same id');
					}
					unset($this->mMap[$lModel->getModelName()][$lId]);
					$lModel = $lModel->getExtendsModel();
				}
			}
		}
		return $lSuccess;
	}
}