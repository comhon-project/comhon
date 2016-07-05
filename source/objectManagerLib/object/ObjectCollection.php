<?php
namespace objectManagerLib\object;

use objectManagerLib\object\object\Object;
use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\object\SerializationUnit;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\exception\PropertyException;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\LocalModel;
use objectManagerLib\object\model\ModelContainer;

class ObjectCollection {
	
	const MAIN_MODEL_OBJECT = 'mainModelObject';
	const INCLUDED_OBJECTS  = 'includedObject';
	
	private $mMap = array();
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	
	private function __construct() {}
	
	/**
	 * get Object with MainModel if exists
	 * @param string|integer $pId
	 * @param string $pMainModelName
	 * @return Object|null
	 */
	public function getMainObject($pId, $pMainModelName) {
		return array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pMainModelName]) 
				? $this->mMap[$pMainModelName][$pId][self::MAIN_MODEL_OBJECT]
				: null;
	}
	
	/**
	 * verify if Object with specified MainModel and id exists in ObjectCollection
	 * @param string|integer $pId
	 * @param string $pMainModelName
	 * @return boolean true if exists
	 */
	public function hasMainObject($pId, $pMainModelName) {
		return array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pMainModelName]);
	}
	
	/**
	 * add object with mainModel (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 */
	public function addMainObject(Object $pObject, $pThrowException = true) {
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
					$this->mMap[$lModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $pObject, self::INCLUDED_OBJECTS => null);
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
	
	public function toString() {
		$lArray = array();
		foreach ($this->mMap as $lMainModelName => $lObjectById) {
			$lArray[$lMainModelName] = array();
			foreach ($lObjectById as $lId => $lObjects) {
				$lArray[$lMainModelName][$lId] = array(
					self::MAIN_MODEL_OBJECT => $lObjects[self::MAIN_MODEL_OBJECT]->toObject(), 
					self::INCLUDED_OBJECTS => $lObjects[self::INCLUDED_OBJECTS]->toObject()
				);
			}
		}
		return json_encode($lArray);
	}
}