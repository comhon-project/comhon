<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Serialization;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\Model;
use Comhon\Object\UniqueObject;

class ForeignValueException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $propertyName
	 */
	public function __construct(UniqueObject $object, $propertyName) {
		$value = $object->getValue($propertyName);
		if ($value instanceof UniqueObject) {
			$value = $value->getId();
		}
		$message = "reference $value of foreign property '$propertyName' for model '{$object->getModel()->getName()}' doesn't exists";
		parent::__construct($message, ConstantException::FOREIGN_CONSTRAINT_EXCEPTION);
	}
	
}