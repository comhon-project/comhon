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
use Comhon\Model\Property\ForeignProperty;

class ForeignValueException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string $properties
	 * @param string $value
	 */
	public function __construct(UniqueObject $object, $properties, $value = null) {
		if (!is_array($properties)) {
			$properties = [$properties];
		}
		if (is_null($value)) {
		$values = [];
			foreach ($properties as $property) {
				$values[] = $object->getValue($property) instanceof UniqueObject ? $object->getValue($property)->getId() : $object->getValue($property);
			}
			$value = '[' . implode(', ', $values) . ']';
		} else {
			$value = "[{$value}]";
		}
		$property = '[' . implode(', ', $properties) . ']';
		
		$message = "reference {$value} of foreign property '$property' for model '{$object->getModel()->getName()}' doesn't exists";
		parent::__construct($message, ConstantException::FOREIGN_CONSTRAINT_EXCEPTION);
	}
	
}