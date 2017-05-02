<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\collection\ObjectCollection;
use comhon\interfacer\Interfacer;

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
	 * @param integer|string $pId
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @param boolean $pIsForeign
	 * @return Object
	 * @throws \Exception
	 */
	protected function _getOrCreateObjectInstance($pId, Interfacer $pInterfacer, $pLocalObjectCollection, $pIsFirstLevel, $pIsForeign = false) {
		$lIsloaded = !$pIsForeign && (!$pIsFirstLevel || $pInterfacer->hasToFlagObjectAsLoaded());
		
		if (is_null($pId) || !$this->hasIdProperties()) {
			$lObject = $this->getObjectInstance($lIsloaded);
		}
		else {
			$lObject = $pLocalObjectCollection->getObject($pId, $this->mModelName);
			if (is_null($lObject)) {
				$lObject = $this->_buildObjectFromId($pId, $lIsloaded, $pInterfacer->hasToFlagValuesAsUpdated());
				$pLocalObjectCollection->addObject($lObject);
			}
			elseif ($lIsloaded || ($pIsFirstLevel && $pInterfacer->getMergeType() !== Interfacer::MERGE)) {
				$lObject->setIsLoaded($lIsloaded);
			}
		}
		return $lObject;
	}
	
	/**
	 * @param string $pInheritanceModelName
	 * @return Model;
	 */
	protected function _getIneritedModel($pInheritanceModelName) {
		$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $this->getMainModelName());
		if (!$lModel->isInheritedFrom($this)) {
			throw new \Exception("model '{$lModel->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $lModel;
	}
	
}