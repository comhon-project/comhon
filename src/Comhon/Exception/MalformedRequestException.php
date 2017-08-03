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

class MalformedRequestException extends ComhonException {
	
	/**
	 * @param string $interval
	 */
	public function __construct($message) {
		parent::__construct($message, ConstantException::MALFORMED_REQUEST_EXCEPTION);
	}
	
}