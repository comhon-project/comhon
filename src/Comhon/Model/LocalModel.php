<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\Interfacer;

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
	 * get or create an instance of ComhonObject
	 * @param integer|string $pId
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @param boolean $pIsForeign
	 * @return ComhonObject
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
	 * @param MainModel $pParentMainModel
	 * @return Model;
	 */
	protected function _getIneritedModel($pInheritanceModelName, MainModel $pParentMainModel) {
		$lModel = ModelManager::getInstance()->getInstanceModel($pInheritanceModelName, $pParentMainModel->getName());
		if (!$lModel->isInheritedFrom($this)) {
			throw new \Exception("model '{$lModel->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $lModel;
	}
	
}