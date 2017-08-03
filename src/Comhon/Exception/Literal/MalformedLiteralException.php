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

class MalformedLiteralException extends ComhonException {
	
	/**
	 * @param \stdClass $stdLiteral
	 */
	public function __construct(\stdClass $stdLiteral) {
		$message = 'malformed literal : '.json_encode($stdLiteral);
		parent::__construct($message, ConstantException::MALFORMED_LITERAL_EXCEPTION);
	}
	
}