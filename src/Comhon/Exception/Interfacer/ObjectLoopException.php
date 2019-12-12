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

class ObjectLoopException extends ComhonException {
	
	public function __construct() {
		parent::__construct('Object loop detected, object contain itself', ConstantException::OBJECT_LOOP_EXCEPTION);
	}
	
}