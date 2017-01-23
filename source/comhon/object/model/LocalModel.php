<?php
namespace comhon\object\model;

use comhon\object\singleton\ModelManager;
use comhon\object\object\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\ObjectCollection;
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
		return $this->mMainModel->getModelName();
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
	protected function _getOrCreateObjectInstance($pId, $pInheritanceModelName, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		if (is_null($pInheritanceModelName)) {
			$lModel = $this;
		} else {
			$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $this->getMainModelName());
			if (!$lModel->isInheritedFrom($this)) {
				throw new \Exception("model '{$lModel->getModelName()}' doesn't inherit from '{$this->getModelName()}'");
			}
		}
		
		if (!$lModel->hasIdProperties()) {
			$lObject = $lModel->getObjectInstance($pIsloaded);
			//trigger_error("new local whithout id $pId, $lModel->mModelName, {$lModel->mMainModel->getModelName()}");
		}
		else {
			$lObject = $pLocalObjectCollection->getObject($pId, $lModel->mModelName);
			if (is_null($lObject)) {
				$lObject = $lModel->_buildObjectFromId($pId, $pIsloaded);
				if (!is_null($pId)) {
					$pLocalObjectCollection->addObject($lObject);
					//trigger_error("add local $pId, $lModel->mModelName, {$lModel->mMainModel->getModelName()}");
				}
				else {
					//trigger_error("new local without add $pId, $lModel->mModelName, {$lModel->mMainModel->getModelName()}");
				}
			} else {
				//trigger_error("local already added $pId, $lModel->mModelName, {$lModel->mMainModel->getModelName()}");
				if ($pUpdateLoadStatus) {
					//trigger_error("update local status ".var_export($lObject->isLoaded(), true));
					$lObject->setLoadStatus();
				}
			}
		}
		return $lObject;
	}
	
}