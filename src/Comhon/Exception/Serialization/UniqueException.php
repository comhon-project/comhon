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

class UniqueException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @param string|string[] $properties
	 * @param string $value
	 */
	public function __construct(UniqueObject $object, $properties, $value = null) {
		if (!is_array($properties)) {
			$properties = [$properties];
		}
		if (is_null($value)) {
			$values = [];
			foreach ($properties as $property) {
				$values[] = $object->getValue($property);
			}
			$value = '[' . implode(', ', $values) . ']';
		} else {
			$value = "[{$value}]";
		}
		$property = '[' . implode(', ', $properties) . ']';
		$message = "value(s) $value of property(ies) $property for model '{$object->getModel()->getName()}' already exists and must be unique";
		parent::__construct($message, ConstantException::UNIQUE_CONSTRAINT_EXCEPTION);
	}
	
}