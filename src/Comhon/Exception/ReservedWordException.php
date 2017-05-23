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

class ReservedWordException extends \Exception {
	
	public function __construct($pWord) {
		parent::__construct("reserved word '$pWord' cannot be used in manifest", ConstantException::RESERVED_WORD_EXCEPTION);
	}
	
}