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

class NotExistingRegexException extends ComhonException {
	
	/**
	 * @param string $regexName
	 */
	public function __construct($regexName) {
		parent::__construct("regex with name '$regexName' doesn't exist", ConstantException::NOT_EXISTING_REGEX_EXCEPTION);
	}
	
}