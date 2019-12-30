<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Model;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class CastStringException extends ComhonException {
	
	/**
	 * 
	 * @param string $value
	 * @param string $expected
	 * @param string $property
	 */
	public function __construct($value, $expected, $property = null) {
		$expected = is_array($expected)
			? 'belong to enumeration '.json_encode($expected)
			: "be $expected";
		$propertyMessage = is_null($property) ? '' : " for property '$property'";
		$message = "Cannot cast value '$value'{$propertyMessage}, value should $expected";
		parent::__construct($message, ConstantException::CAST_EXCEPTION);
	}
	
}