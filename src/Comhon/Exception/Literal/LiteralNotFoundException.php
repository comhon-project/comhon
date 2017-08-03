<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Literal;

use Comhon\Exception\ConstantException;
use Comhon\Exception\ComhonException;

class LiteralNotFoundException extends ComhonException {
	
	/**
	 * @param string|integer|float $id
	 */
	public function __construct($id) {
		$message = "literal with id '$id' not found in literal collection";
		parent::__construct($message, ConstantException::LITERAL_NOT_FOUND_EXCEPTION);
	}
	
}