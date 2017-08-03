<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception;

use Comhon\Object\ComhonObject;

class UnexpectedValueTypeException extends ComhonException {
	
	/**
	 * @var string
	 */
	private $expectedType;
	
	/**
	 * @param mixed $value
	 * @param string $expectedType
	 * @param string $property
	 */
	public function __construct($value, $expectedType, $property = null) {
		$this->expectedType = $expectedType;
		if (is_object($value)) {
			if ($value instanceof ComhonObject) {
				$type = $value->getComhonClass();
			} else {
				$type = get_class($value);
			}
		} else {
			$type = gettype($value);
		}
		$stringValue = is_object($value) || is_array($value) 
			? ' '
			: " '" . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "' ";
		
		$stringProperty = is_null($property) ? ' ' : " of property '$property' ";
		
		$message = "value{$stringProperty}must be a $expectedType, $type{$stringValue}given";
		parent::__construct($message, ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION);
		
	}
	
	/**
	 * get expected type
	 * 
	 * @return string
	 */
	public function getExpectedType() {
		return $this->expectedType;
	}
	
}