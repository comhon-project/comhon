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
	 * @param string $pMainModelName
	 * @param string|integer $pId
	 * @return Object|null
	 */
	public function getMainObject($pId, $pMainModelName) {
		return array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pId, $this->mMap[$pMainModelName]) 
				? $this->mMap[$pMainModelName][$pId][self::MAIN_MODEL_OBJECT]
				: null;
	}
	
	/**
	 * add object with mainModel if needed
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if an object with same id already exists
	 * @throws \Exception
	 * @return string|integer 
	 * - return object id if object has id and (not already added or same instance)
	 * - return object spl_object_hash if object hasn't id or (already added and different instance)
	 */
	public function addMainObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		$lModelName = $pObject->getModel()->getModelName();
		$lId        = $pObject->getId();
		
		if (is_null($lId)) {
			$lId = spl_object_hash($pObject);
		}
		if (!array_key_exists($lModelName, $this->mMap)) {
			$this->mMap[$lModelName] = array();
		}
		// if key doesn't exists => create new key with object id or instance id (spl_object_hash)
		if (!array_key_exists($lId, $this->mMap[$lModelName])) {
			$this->mMap[$lModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $pObject, self::INCLUDED_OBJECTS => array());
		}
		// else if must throw exception => throw exception
		else if ($pThrowException) {
			throw new \Exception('object already added');
		}
		// else if id exists but not same object => create new key with instance id (spl_object_hash)
		else if (spl_object_hash($pObject) != spl_object_hash($this->mMap[$lModelName][$lId][self::MAIN_MODEL_OBJECT])) {
			trigger_error("should never happen");
			$lId = spl_object_hash($pObject);
			$this->mMap[$lModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $pObject, self::INCLUDED_OBJECTS => array());
		}
		
		return $lId;
	}
	
	/**
	 * get Object with LocalModel if exists
	 * @param string|integer $pId
	 * @param string $pModelName
	 * @param string|integer $pMainModelId
	 * @param string $pMainModelName
	 * @return Object|null
	 */
	public function getLocalObject($pId, $pModelName, $pMainModelId, $pMainModelName) {
		return array_key_exists($pMainModelName, $this->mMap) && array_key_exists($pMainModelId, $this->mMap[$pMainModelName])
				&& array_key_exists($pModelName, $this->mMap[$pMainModelName][$pMainModelId][self::INCLUDED_OBJECTS])
				&& array_key_exists($pId, $this->mMap[$pMainModelName][$pMainModelId][self::INCLUDED_OBJECTS][$pModelName])
					? $this->mMap[$pMainModelName][$pMainModelId][self::INCLUDED_OBJECTS][$pModelName][$pId]
					: null;
	}
	
	/**
	 * add object with localModel (only if not already added)
	 * @param Object $pObject
	 * @param string|integer $pMainObjectId
	 * @param boolean $pThrowException throw exception if an object with same id already exists
	 * @return boolean true if object is added
	 */
	public function addLocalObject(Object $pObject, $pMainObjectId, $pThrowException = true) {
		if (!($pObject->getModel() instanceof LocalModel)) {
			throw new \Exception('mdodel must be instance of LocalModel');
		}
		$lReturn        = false;
		$lMainModelName = $pObject->getModel()->getMainModelName();
		$lId            = $pObject->getId();
		
		if (is_null($lId)) {
			return $lReturn;
		}
		if (isset($this->mMap[$lMainModelName][$pMainObjectId])) {
			$lIncludedObjects = &$this->mMap[$lMainModelName][$pMainObjectId][self::INCLUDED_OBJECTS];
			$pModelName = $pObject->getModel()->getModelName();
			if (!array_key_exists($pModelName, $lIncludedObjects)) {
				$lIncludedObjects[$pModelName] = array();
			}
			if (!array_key_exists($lId, $lIncludedObjects[$pModelName])) {
				$lIncludedObjects[$pModelName][$lId] = $pObject;
				$lReturn = true;
			} else if ($pThrowException) {
				throw new \Exception('object already added');
			}
		}
		
		return $lReturn;
	}
	
	public function toString() {
		$lArray = array();
		foreach ($this->mMap as $lMainModelName => $lObjectById) {
			$lArray[$lMainModelName] = array();
			foreach ($lObjectById as $lId => $lObjects) {
				$lArray[$lMainModelName][$lId] = array(self::MAIN_MODEL_OBJECT => $lObjects[self::MAIN_MODEL_OBJECT]->toObject(), self::INCLUDED_OBJECTS => array());
				foreach ($lObjects[self::INCLUDED_OBJECTS] as $lModelName => $lUnserializableObjectById) {
					foreach ($lUnserializableObjectById as $lOtherId => $lObject) {
						$lArray[$lMainModelName][$lId][self::INCLUDED_OBJECTS][$lModelName][$lOtherId] = $lObject->toObject();
					}
				}
			}
		}
		return json_encode($lArray);
	}
}