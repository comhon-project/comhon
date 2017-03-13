<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\object\serialization\SqlTable;
use comhon\object\Object;
use comhon\object\ObjectArray;
use comhon\object\collection\ObjectCollection;
use \stdClass;

class LocalModel extends Model {
	
	private $mMainModel = null;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public function __construct($pModelName, $pMainModelName, $pLoadModel) {
		$this->mMainModel = ModelManager::getInstance()->getInstanceModel($pMainModelName);
		parent::__construct($pModelName, $pLoadModel);
	}
	
	public function getMainModel() {
		return $this->mMainModel;
	}
	
	public function getMainModelName() {
		return $this->mMainModel->getName();
	}
	
	/**
	 * get or create an instance of Object
	 * @param string|integer $pId
	 * @param string $pInheritanceModelName
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status 
	 * @return Object
	 */
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true, $pFlagAsUpdated = true) {
		if (is_null($pInheritanceModelName)) {
			$lModel = $this;
		} else {
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $this->getMainModelName());
			if (!$lModel->isInheritedFrom($this)) {
				throw new \Exception("model '{$lModel->getName()}' doesn't inherit from '{$this->getName()}'");
			}
		}
		
		if (!$lModel->hasIdProperties()) {
			$lObject = $lModel->getObjectInstance($pIsloaded);
		}
		else {
			$lObject = $pLocalObjectCollection->getObject($pId, $lModel->mModelName);
			if (is_null($lObject)) {
				$lObject = $lModel->_buildObjectFromId($pId, $pIsloaded, $pFlagAsUpdated);
				if (!is_null($pId)) {
					$pLocalObjectCollection->addObject($lObject);
				}
			} else {
				if ($pUpdateLoadStatus) {
					$lObject->setLoadStatus();
				}
			}
		}
		return $lObject;
	}
	
}