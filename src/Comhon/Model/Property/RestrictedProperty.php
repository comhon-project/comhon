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

class RestrictedProperty extends Property {
	
	private $restriction;
	
	/**
	 *
	 * @param Model $model
	 * @param string $name
	 * @param string $serializationName
	 * @param boolean $isId
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 * @param mixed $default
	 * @param unknown $restriction
	 * @param boolean $isInterfacedAsNodeXml
	 * @param Restriction $restriction
	 * @throws \Exception
	 */
	public function __construct(Model $model, $name, Restriction $restriction, $serializationName = null, $isId = false, $isPrivate = false, $isSerializable = true, $default = null, $isInterfacedAsNodeXml = null) {
		parent::__construct($model, $name, $serializationName, false, $isPrivate, $isSerializable);
		if (!$restriction->isAllowedModel($this->model)) {
			throw new \Exception('restriction doesn\'t allow specified model');
		}
		$this->restriction = $restriction;
	}
	
	/**
	 * verify if property is exportable in public/private/serialization mode
	 *
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @param mixed $value value that we want to export
	 * @return boolean true if property is interfaceable
	 */
	public function isExportable($private, $serialization, $value) {
		$this->isSatisfiable($value, true);
		return parent::isExportable($private, $serialization, $value);
	}
	
	/**
	 * verify if value is satisfiable regarding restriction property
	 *
	 * @param mixed $value
	 * @param boolean $throwException
	 * @return boolean true if property is satisfiable
	 */
	public function isSatisfiable($value, $throwException = false) {
		$isSatisfiable = $this->restriction->satisfy($value);
		if (!$isSatisfiable && $throwException) {
			throw new NotSatisfiedRestrictionException($value, $this->restriction);
		}
		return $isSatisfiable;
	}
	
}