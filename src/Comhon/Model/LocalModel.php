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
use Comhon\Interfacer\Interfacer;

class LocalModel extends Model {
	
	/** @var MainModel */
	private $mainModel = null;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 * 
	 * @param string $modelName
	 * @param string $mainModelName
	 * @param boolean $loadModel
	 */
	public function __construct($modelName, $mainModelName, $loadModel) {
		$this->mainModel = ModelManager::getInstance()->getInstanceModel($mainModelName);
		parent::__construct($modelName, $loadModel);
	}
	
	
	/**
	 * get main model
	 * 
	 * @return Model
	 */
	public function getMainModel() {
		return $this->mainModel;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getMainModelName()
	 */
	public function getMainModelName() {
		return $this->mainModel->getName();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_getOrCreateObjectInstance()
	 */
	protected function _getOrCreateObjectInstance($id, Interfacer $interfacer, $localObjectCollection, $isFirstLevel, $isForeign = false) {
		$isloaded = !$isForeign && (!$isFirstLevel || $interfacer->hasToFlagObjectAsLoaded());
		
		if (is_null($id) || !$this->hasIdProperties()) {
			$object = $this->getObjectInstance($isloaded);
		}
		else {
			$object = $localObjectCollection->getObject($id, $this->modelName);
			if (is_null($object)) {
				$object = $this->_buildObjectFromId($id, $isloaded, $interfacer->hasToFlagValuesAsUpdated());
				$localObjectCollection->addObject($object);
			}
			elseif ($isloaded || ($isFirstLevel && $interfacer->getMergeType() !== Interfacer::MERGE)) {
				$object->setIsLoaded($isloaded);
			}
		}
		return $object;
	}
	
	/**
	 * get inherited model
	 * 
	 * @param string $inheritanceModelName
	 * @param MainModel $mainModelContainer
	 * @return Model;
	 */
	protected function _getIneritedModel($inheritanceModelName, MainModel $mainModelContainer) {
		$model = ModelManager::getInstance()->getInstanceModel($inheritanceModelName, $mainModelContainer->getName());
		if (!$model->isInheritedFrom($this)) {
			throw new \Exception("model '{$model->getName()}' doesn't inherit from '{$this->getName()}'");
		}
		return $model;
	}
	
}