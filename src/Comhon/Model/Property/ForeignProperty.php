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

use Comhon\Model\Model;
use Comhon\Object\ObjectUnique;
use Comhon\Object\Object;

class ForeignProperty extends Property {
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 */
	public function __construct(Model $model, $name, $serializationName = null, $isPrivate = false, $isSerializable = true) {
		parent::__construct($model, $name, $serializationName, false, $isPrivate, $isSerializable);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::loadValue()
	 */
	public function loadValue(ObjectUnique $object, $propertiesFilter = null, $forceLoad = false) {
		$this->getModel()->verifValue($object);
		if ($object->isLoaded() && !$forceLoad) {
			return false;
		}
		$serializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($serializationUnit)) {
			return false;
		}
		return $serializationUnit->loadObject($object, $propertiesFilter);
	}
	
	/**
	 * verify if property has serialization with specified type
	 * 
	 * @param string $serializationType
	 * @return boolean
	 */
	public function hasSerializationUnit($serializationType) {
		return $this->getUniqueModel()->hasSerializationUnit($serializationType);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isForeign()
	 */
	public function isForeign() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isComplex()
	 */
	public function isComplex() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isInterfaceable()
	 */
	public function isInterfaceable($private, $serialization) {
		return parent::isInterfaceable($private, $serialization) && ($private || !$this->getUniqueModel()->hasPrivateIdProperty());
	}
	
}