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
use Comhon\Model\Model;
use Comhon\Exception\NotSatisfiedRestrictionException;
use Comhon\Exception\ComhonException;

class RestrictedProperty extends Property {
	
	/** @var \Comhon\Model\Restriction\Restriction */
	private $restriction;
	
	/**
	 *
	 * @param \Comhon\Model\Model $model
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
	public function __construct(Model $model, $name, Restriction $restriction, $serializationName = null, $isId = false, $isPrivate = false, $isSerializable = true, $default = null, $isInterfacedAsNodeXml = null) {
		parent::__construct($model, $name, $serializationName, false, $isPrivate, $isSerializable);
		if (!$restriction->isAllowedModel($this->model)) {
			throw new ComhonException('restriction doesn\'t allow specified model');
		}
		$this->restriction = $restriction;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isExportable()
	 */
	public function isExportable($private, $serialization, $value) {
		$this->isSatisfiable($value, true);
		return parent::isExportable($private, $serialization, $value);
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
	
}