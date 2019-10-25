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

use Comhon\Model\Restriction\Restriction;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\ComhonException;
use Comhon\Model\AbstractModel;

class RestrictedProperty extends Property {
	
	/** @var \Comhon\Model\Restriction\Restriction */
	private $restriction;
	
	/**
	 *
	 * @param \Comhon\Model\AbstractModel $model
	 * @param string $name
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 * @param mixed $default
	 * @param boolean $isInterfacedAsNodeXml
	 * @throws \Exception
	 */
	public function __construct(AbstractModel $model, $name, Restriction $restriction, $serializationName = null, $isId = false, $isPrivate = false, $isSerializable = true, $default = null, $isInterfacedAsNodeXml = null) {
		parent::__construct($model, $name, $serializationName, $isId, $isPrivate, $isSerializable, $default, $isInterfacedAsNodeXml);
		if (!$restriction->isAllowedModel($this->model)) {
			throw new ComhonException('restriction doesn\'t allow specified model');
		}
		$this->restriction = $restriction;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isSatisfiable()
	 */
	public function isSatisfiable($value, $throwException = false) {
		$isSatisfiable = $this->restriction->satisfy($value);
		if (!$isSatisfiable && $throwException) {
			throw new NotSatisfiedRestrictionException($value, $this->restriction);
		}
		return $isSatisfiable;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isEqual()
	 */
	public function isEqual(Property $property) {
		return $this === $property || (
			parent::isEqual($property) &&
			($property instanceof RestrictedProperty) &&
			$this->restriction->isEqual($property->restriction) 
		);
	}
	
}