<?php
namespace comhon\object;

use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\object\model\MainModel;

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
	 * add object with mainModel (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		
		if ($pObject->hasCompleteId()) {
			if ($pObject->getModel()->hasIdProperty()) {
				$lModelName = $pObject->getModel()->getModelName();
				$lId = $pObject->getId();
				if (!array_key_exists($lModelName, $this->mMap)) {
					$this->mMap[$lModelName] = array();
				}
				// if object NOT already added, we can add it and create attached LocalObjectCollection
				if(!array_key_exists($lId, $this->mMap[$lModelName])) {
					$this->mMap[$lModelName][$lId] = $pObject;
				}
				// else if must throw exception => throw exception
				else if ($pThrowException) {
					throw new \Exception('object already added');
				}
			}
		}
		// else if must throw exception => throw exception
		else if ($pThrowException) {
			trigger_error(json_encode($pObject->toObject()));
			trigger_error($pObject->getModel()->getModelName());
			trigger_error(json_encode($pObject->getModel()->getIdproperties()));
			throw new \Exception('object can\'t be added, object has no id or id is incomplete');
		}
	}
	
}