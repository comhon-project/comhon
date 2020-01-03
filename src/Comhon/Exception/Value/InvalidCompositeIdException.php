<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Value;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class InvalidCompositeIdException extends ComhonException {
	
	/**
	 * @param mixed $value
	 * @param string $expectedType
	 * @param string $property
	 */
	public function __construct($id) {
		parent::__construct("invalid composite id '$id'", ConstantException::INVALID_COMPOSITE_ID_EXCEPTION);
		
	}
	
}