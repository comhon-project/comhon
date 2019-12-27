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

class MissingIdForeignValueException extends ComhonException {
	
	public function __construct() {
		parent::__construct('missing or not complete id on foreign value', ConstantException::MISSING_ID_FOREIGN_VALUE_EXCEPTION);
		
	}
	
}