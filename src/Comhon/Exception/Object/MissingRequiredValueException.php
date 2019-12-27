<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Object;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Object\UniqueObject;

class MissingRequiredValueException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $propertyName
	 * @param string $unset
	 */
	public function __construct(UniqueObject $object, $propertyName, $unset = false) {
		$message = $unset
			? "impossible to unset required value '$propertyName' on comhon object with model '{$object->getModel()->getName()}'"
			: "missing required value '$propertyName' on comhon object with model '{$object->getModel()->getName()}'";
		parent::__construct($message, ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
	}
	
}