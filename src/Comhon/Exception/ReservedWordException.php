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

class ReservedWordException extends ComhonException {
	
	/**
	 * @param string $word
	 */
	public function __construct($word) {
		parent::__construct("reserved word '$word' cannot be used in manifest", ConstantException::RESERVED_WORD_EXCEPTION);
	}
	
}