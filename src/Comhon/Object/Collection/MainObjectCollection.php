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

use Comhon\Model\MainModel;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\ComhonException;

class MainObjectCollection extends ObjectCollection {
	
	private  static $_instance;
	
	/**
	 * get MainObjectCollection instance
	 * 
	 * @return \Comhon\Object\Collection\MainObjectCollection
	 */
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	
	private function __construct() {}
	
	/**
	 * add object with mainModel (if not already added)
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @param boolean $throwException throw exception if object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(ObjectUnique $object, $throwException = true) {
		if (!($object->getModel() instanceof MainModel)) {
			throw new ComhonException('model of given ObjectUnique must be instance of MainModel');
		}
		return parent::addObject($object, $throwException);
	}
	
	
	/**
	 * add object with mainModel (if not already added)
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function removeObject(ObjectUnique $object) {
		if (!($object->getModel() instanceof MainModel)) {
			throw new ComhonException('model of given ObjectUnique must be instance of MainModel');
		}
		return parent::removeObject($object);
	}
}