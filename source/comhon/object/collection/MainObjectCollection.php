<?php
namespace comhon\object\collection;

use comhon\object\Object;
use comhon\model\MainModel;

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
	 * @param boolean $pThrowException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(Object $pObject, $pThrowException = true) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		return parent::addObject($pObject, $pThrowException);
	}
	
	
	/**
	 * add object with mainModel (if not already added)
	 * @param Object $pObject
	 * @param boolean $pThrowException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(Object $pObject) {
		if (!($pObject->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		return parent::removeObject($pObject);
	}
}