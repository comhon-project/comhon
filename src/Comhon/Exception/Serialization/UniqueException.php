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
	 * @param string[] $propertiesNames
	 */
	public function __construct(UniqueObject $object, array $propertiesNames) {
		if (empty($propertiesNames)) {
			$propertiesNames = ['Unknown'];
		}
		foreach ($propertiesNames as $propertyName) {
			$value = $object->getValue($propertyName);
			if ($value instanceof UniqueObject) {
				$value = $value->getId();
			}
			$values[] = $value;
		}
		if (count($propertiesNames) > 1) {
			$messageValues = implode(', ', $values);
			$messageProperties = implode(', ', $propertiesNames);
			$messageValueWord = 'values';
			$messagePropertyWord = 'properties';
		} else {
			$messageValues = $values[0];
			$messageProperties = $propertiesNames[0];
			$messageValueWord = 'value';
			$messagePropertyWord = 'property';
		}
		$message = "$messageValueWord $messageValues of $messagePropertyWord $messageProperties for model '{$object->getModel()->getName()}' already exists and must be unique";
		parent::__construct($message, ConstantException::UNIQUE_CONSTRAINT_EXCEPTION);
	}
	
}