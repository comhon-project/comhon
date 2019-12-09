<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Interfacer;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Object\UniqueObject;

class NotReferencedValueException extends ComhonException {
	
	/**
	 * 
	 * @param UniqueObject $object
	 */
	public function __construct(UniqueObject $object) {
		$message = "foreign value with model '{$object->getModel()->getName()}' and id '{$object->getId()}' not referenced in  interfaced object";
		parent::__construct($message, ConstantException::NOT_REFERENCED_VALUE_EXCEPTION);
	}
	
}