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

class MalformedClauseException extends ComhonException {
	
	/**
	 * @param \stdClass $stdClause
	 */
	public function __construct(\stdClass $stdClause) {
		$message = 'malformed clause : '.json_encode($stdClause);
		parent::__construct($message, ConstantException::MALFORMED_LITERAL_EXCEPTION);
	}
	
}