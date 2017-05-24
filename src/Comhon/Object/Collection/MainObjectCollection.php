<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object\Collection;

use Comhon\Object\ComhonObject;
use Comhon\Model\MainModel;

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
	 * @param ComhonObject $object
	 * @param boolean $throwException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(ComhonObject $object, $throwException = true) {
		if (!($object->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		return parent::addObject($object, $throwException);
	}
	
	
	/**
	 * add object with mainModel (if not already added)
	 * @param ComhonObject $object
	 * @param boolean $throwException throw exception if object can't be added (no complete id or object already added)
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(ComhonObject $object) {
		if (!($object->getModel() instanceof MainModel)) {
			throw new \Exception('mdodel must be instance of MainModel');
		}
		return parent::removeObject($object);
	}
}