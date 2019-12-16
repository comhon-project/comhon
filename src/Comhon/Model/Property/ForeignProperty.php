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

use Comhon\Object\UniqueObject;
use Comhon\Model\ModelForeign;
use Comhon\Model\Restriction\NotNull;

class ForeignProperty extends Property {
	
	/**
	 * 
	 * @param \Comhon\Model\ModelForeign $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isPrivate
	 * @param boolean $isRequired
	 * @param boolean $isSerializable
	 * @param boolean $isNotNull
	 */
	public function __construct(ModelForeign $model, $name, $serializationName = null, $isPrivate = false, $isRequired = false, $isSerializable = true, $isNotNull = false) {
		if ($isNotNull) {
			parent::__construct($model, $name, $serializationName, false, $isPrivate, $isRequired, $isSerializable, null, null, [new NotNull()]);
		} else {
			parent::__construct($model, $name, $serializationName, false, $isPrivate, $isRequired, $isSerializable);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::loadValue()
	 */
	public function loadValue(UniqueObject $object, $propertiesFilter = null, $forceLoad = false) {
		$this->getModel()->verifValue($object);
		if ($object->isLoaded() && !$forceLoad) {
			return false;
		}
		$serialization = $this->getUniqueModel()->getSerialization();
		if (is_null($serialization)) {
			return false;
		}
		return $serialization->getSerializationUnit()->loadObject($object, $propertiesFilter);
	}
	
	/**
	 * verify if property has serialization with specified type
	 * 
	 * @param string $serializationType
	 * @return boolean
	 */
	public function hasSerialization($serializationType) {
		return $this->getUniqueModel()->hasSerialization($serializationType);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isForeign()
	 */
	public function isForeign() {
		return true;
	}
	
}