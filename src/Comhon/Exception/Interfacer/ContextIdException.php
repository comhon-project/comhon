<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Interfacer;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class ContextIdException extends ComhonException {
	
	public function __construct() {
		parent::__construct('Cannot interface foreign value with private id in public context', ConstantException::CONTEXT_ID_EXCEPTION);
	}
	
}