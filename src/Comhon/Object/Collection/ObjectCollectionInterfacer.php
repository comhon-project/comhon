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

use Comhon\Object\UniqueObject;

class ObjectCollectionInterfacer {
	
	/**
	 *
	 * @var ObjectCollection
	 */
	private $startObjectCollection;
	
	/**
	 *
	 * @var ObjectCollection
	 */
	private $newObjectCollection;
	
	/**
	 *
	 * @var ObjectCollection
	 */
	private $newForeignObjectCollection;
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object if specified, populate start object collection
	 */
	public function __construct(UniqueObject $object = null) {
		$this->startObjectCollection = is_null($object) ? new ObjectCollection() : ObjectCollection::build($object);
		$this->newForeignObjectCollection = new ObjectCollection();
		$this->newObjectCollection = new ObjectCollection();
	}
	
	/**
	 * get comhon object with specified model name if exists in :
	 * - new object collection
	 * - new foreign object collection
	 * - start object collection
	 * 
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model that share same id
	 * @return \Comhon\Object\UniqueObject|null null if not found
	 */
	public function getObject($id, $modelName, $inlcudeInheritance = true) {
		if (!is_null($obj = $this->newObjectCollection->getObject($id, $modelName, $inlcudeInheritance))) {
			return $obj;
		}
		if (!is_null($obj = $this->newForeignObjectCollection->getObject($id, $modelName, $inlcudeInheritance))) {
			return $obj;
		}
		return $this->startObjectCollection->getObject($id, $modelName, $inlcudeInheritance);
	}
	
	/**
	 * verify if comhon object with specified model name and id exists in new object collection
	 *
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model that share same id
	 * @return boolean true if exists
	 */
	public function hasNewObject($id, $modelName, $inlcudeInheritance = true) {
		return $this->newObjectCollection->hasObject($id, $modelName, $inlcudeInheritance);
	}
	
	/**
	 * add comhon object (if not already added) in :
	 * - new object collection if $isForeign is false
	 * - new foreign object collection if $isForeign is true
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param boolean $isForeign it true, add $object in new foreign object collection otherwise in new object collection
	 * @param boolean $throwException it true, throw exception if another instance object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addObject(UniqueObject $object, $isForeign, $throwException = true) {
		if ($isForeign) {
			return $this->newForeignObjectCollection->addObject($object, $throwException);
		}
		return $this->newObjectCollection->addObject($object, $throwException);
	}
	
	/**
	 * verify if comhon object with specified model name and id exists in start object collection
	 *
	 * @param string|integer $id
	 * @param string $modelName
	 * @param boolean $inlcudeInheritance if true, search in extended model that share same id
	 * @return boolean true if exists
	 */
	public function hasStartObject($id, $modelName, $inlcudeInheritance = true) {
		return $this->startObjectCollection->hasObject($id, $modelName, $inlcudeInheritance);
	}
	
	/**
	 * add comhon object (if not already added) start object collection
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @param boolean $throwException it true, throw exception if another instance object already added
	 * @throws \Exception
	 * @return boolean true if object is added
	 */
	public function addStartObject(UniqueObject $object, $throwException = true) {
		return $this->startObjectCollection->addObject($object, $throwException);
	}
	
	/**
	 * replace new object collection
	 * 
	 * @param \Comhon\Object\Collection\ObjectCollection $newObjectCollection
	 */
	public function replaceNewObjectCollection(ObjectCollection $newObjectCollection) {
		$this->newObjectCollection = $newObjectCollection;
	}
	
	/**
	 * get objects in $newForeignObjectCollection that are not referenced in $newObjectCollection.
	 * if object has a main model, it is not taken in account.
	 * 
	 * @return  \Comhon\Object\UniqueObject[]
	 */
	public function getNotReferencedObjects() {
		$notReferencedObjects = [];
		// var_dump('-------------');
		// var_dump($this->newForeignObjectCollection->toString());
		// var_dump($this->newObjectCollection->toString());
		foreach ($this->newForeignObjectCollection->getCollection() as $modelName => $objects) {
			foreach ($objects as $id => $object) {
				if (!$object->getModel()->isMain() && !$this->newObjectCollection->hasObject($id, $modelName)) {
					// echo $object;
					$notReferencedObjects[] = $object;
				}
			}
		}
		return $notReferencedObjects;
	}
	
}