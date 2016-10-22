<?php
namespace comhon\object\model;

use comhon\object\singleton\InstanceModel;
use comhon\object\object\SqlTable;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\ObjectCollection;
use \stdClass;

class LocalModel extends Model {
	
	private $mMainModel = null;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public function __construct($pModelName, $pMainModelName, $pLoadModel) {
		$this->mMainModel = InstanceModel::getInstance()->getInstanceModel($pMainModelName);
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
	 * @param LocalObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsloaded
	 * @param boolean $pUpdateLoadStatus if true and object already exists update load status 
	 * @return array [Object,LocalObjectCollection] second element is $pLocalObjectCollection
	 */
	protected function _getOrCreateObjectInstance($pId, $pLocalObjectCollection, $pIsloaded = true, $pUpdateLoadStatus = true) {
		if (!$this->hasIdProperty()) {
			$lObject = $this->getObjectInstance($pIsloaded);
			//trigger_error("new local whithout id $pId, $this->mModelName, {$this->mMainModel->getModelName()}");
		}
		else {
			$lObject = $pLocalObjectCollection->getObject($pId, $this->mModelName);
			if (is_null($lObject)) {
				$lObject = $this->_buildObjectFromId($pId, $pIsloaded);
				if (!is_null($pId)) {
					$pLocalObjectCollection->addObject($lObject);
					//trigger_error("add local $pId, $this->mModelName, {$this->mMainModel->getModelName()}");
				}
				else {
					//trigger_error("new local without add $pId, $this->mModelName, {$this->mMainModel->getModelName()}");
				}
			} else {
				//trigger_error("local already added $pId, $this->mModelName, {$this->mMainModel->getModelName()}");
				if ($pUpdateLoadStatus) {
					//trigger_error("update local status ".var_export($lObject->isLoaded(), true));
					$lObject->setLoadStatus();
				}
			}
		}
		return $lObject;
	}
	
}