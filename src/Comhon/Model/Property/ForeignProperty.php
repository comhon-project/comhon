<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Property;

use Comhon\Object\ComhonObject;
use Comhon\Model\Model;

class ForeignProperty extends Property {
	
	public function __construct(Model $model, $name, $serializationName = null, $isPrivate = false, $isSerializable = true) {
		parent::__construct($model, $name, $serializationName, false, $isPrivate, $isSerializable);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValue(ComhonObject $object, $propertiesFilter = null, $forceLoad = false) {
		$this->getModel()->verifValue($object);
		if ($object->isLoaded() && !$forceLoad) {
			return false;
		}
		if ($object->getModel() !== $this->getUniqueModel() && !$object->getModel()->isInheritedFrom($this->getUniqueModel())) {
			$reflexion1 = new \ReflectionClass(get_class($object->getModel()));
			$reflexion2 = new \ReflectionClass(get_class($this->getUniqueModel()));
			throw new \Exception("object not compatible with property : {$object->getModel()->getName()} ({$reflexion1->getShortName()}) | {$this->getUniqueModel()->getName()} ({$reflexion2->getShortName()})");
		}
		$serializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($serializationUnit)) {
			return false;
		}
		return $serializationUnit->loadObject($object, $propertiesFilter);
	}
	
	public function getSerialization() {
		return $this->getUniqueModel()->getSerialization();
	}
	
	public function hasSerializationUnit($serializationType) {
		return $this->getUniqueModel()->hasSerializationUnit($serializationType);
	}
	
	public function hasSqlTableUnit() {
		return $this->getUniqueModel()->hasSqlTableUnit();
	}
	
	public function getSqlTableUnit() {
		return $this->getUniqueModel()->getSqlTableUnit();
	}
	
	public function isForeign() {
		return true;
	}
	
	public function isComplex() {
		return true;
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($private, $serialization) {
		return parent::isInterfaceable($private, $serialization) && ($private || !$this->getUniqueModel()->hasPrivateIdProperty());
	}
	
}