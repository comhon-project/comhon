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

class EnumerationException extends ComhonException {
	
	/**
	 * @param mixed $value
	 * @param mixed[] $enum
	 * @param string $property
	 */
	public function __construct($value, array $enum, $property) {
		$message = "value '$value' of property '$property' doesn't belong to enumeration '".json_encode($enum)."'";
		parent::__construct($message, ConstantException::ENUMERATION_EXCEPTION);
	}
	
}