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

class SerializationException extends ComhonException {
	
	/**
	 * @param string $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
	
}