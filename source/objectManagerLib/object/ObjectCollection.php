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
	 * add object with mainModel if needed
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no id or already added)
	 * @throws \Exception
	 * @return LocalObjectCollection
	 * - if Main object has been added or same instance with existing object
	 * 		return LocalObjectCollection attached to ObjectCollection (retrievable)
	 * - otherwise
	 * 		return LocalObjectCollection NOT attached to ObjectCollection (NOT retrievable)
	 */
	public function addMainObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		$lModelName = $pObject->getModel()->getModelName();
		
		// if model doesn't contained id, create new LocalObjectCollection NOT attached to ObjectCollection
		if (!$pObject->getModel()->hasIdProperty()) {
			$lLocalObjectCollection = new LocalObjectCollection();
		}
		// else if object has a complete id
		else if ($pObject->hasCompleteId()) {
			$lId = $pObject->getId();
			if (!array_key_exists($lModelName, $this->mMap)) {
				$this->mMap[$lModelName] = array();
			}
			// if object NOT already added, we can add it and create attached LocalObjectCollection
			if(!array_key_exists($lId, $this->mMap[$lModelName])) {
				$lLocalObjectCollection = new LocalObjectCollection();
				$this->mMap[$lModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $pObject, self::INCLUDED_OBJECTS => $lLocalObjectCollection);
			}
			// else if must throw exception => throw exception
			else if ($pThrowException) {
				throw new \Exception('object already added');
			}
			// else if same object instance, get LocalObjectCollection previously created
			else if ($pObject === $this->mMap[$lModelName][$lId][self::MAIN_MODEL_OBJECT]) {
				$lLocalObjectCollection = $this->mMap[$lModelName][$lId][self::MAIN_MODEL_OBJECT];
			}
			// else create new LocalObjectCollection NOT attached to ObjectCollection
			else {
				trigger_error("should never happen");
				$lLocalObjectCollection = new LocalObjectCollection();
			}
		}
		// else if must throw exception => throw exception
		else if ($pThrowException) {
			trigger_error(json_encode($pObject->toObject()));
			trigger_error($pObject->getModel()->getModelName());
			trigger_error(json_encode($pObject->getModel()->getIdproperties()));
			throw new \Exception('object can\'t be added, object has no id or id is incomplete');
		}
		// else create new LocalObjectCollection NOT attached to ObjectCollection
		else {
			$lLocalObjectCollection = new LocalObjectCollection();
		}
		
		return $lLocalObjectCollection;
	}
	
	/**
	 * get LocalObjectCollection attached to a main object
	 * @param string|integer $pMainObjectId
	 * @param string $pMainModelName
	 * @param boolean $pThrowException if true, throw exception if not found
	 * @return Object|null
	 */
	public function getLocalObjectCollection($pMainObjectId, $pMainModelName, $pThrowException = false) {
		if (array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pMainObjectId, $this->mMap[$pMainModelName])) {
			return $this->mMap[$pMainModelName][$pMainObjectId][self::INCLUDED_OBJECTS];
		}else if ($pThrowException) {
			throw new \Exception('LocalObjectCollection not found');
		}
		return null;
	}
	
	/**
	 * get Object with LocalModel if exists
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @param string|integer $pMainObjectId
	 * @param string $pMainModelName
	 * @return Object|null
	 */
	public function getLocalObject($pId, $pModelName, $pLocalObjectCollection, $pMainModelName) {
		return array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pMainObjectId, $this->mMap[$pMainModelName])
					? $this->mMap[$pMainModelName][$pMainObjectId][self::INCLUDED_OBJECTS]->getObject($pId, $pModelName)
					: null;
	}
	
	/**
	 * add object with localModel (only if not already added)
	 * @param Object $pObject
	 * @param string|integer $pMainObjectId
	 * @param boolean $pThrowException throw exception if an object with same id already exists
	 * @return boolean true if object is added
	 */
	public function addLocalObject(Object $pObject, $pLocalObjectCollection, $pThrowException = true) {
		$lMainModelName = $pObject->getModel()->getMainModelName();
		if ($this->hasMainObject($pMainObjectId, $lMainModelName)) {
			return $this->mMap[$lMainModelName][$pMainObjectId][self::INCLUDED_OBJECTS]->addObject($pObject, $pThrowException);
		}
		return false;
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