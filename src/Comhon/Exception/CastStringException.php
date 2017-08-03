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

class CastStringException extends ComhonException {
	
	/**
	 * 
	 * @param string $value
	 * @param string $expected
	 */
	public function __construct($value, $expected) {
		$expected = is_array($expected)
			? 'belong to enumeration '.json_encode($expected)
			: "be $expected";
		
		$message = "Cannot cast value '$value', value should $expected";
		parent::__construct($message, ConstantException::CAST_EXCEPTION);
	}
	
}