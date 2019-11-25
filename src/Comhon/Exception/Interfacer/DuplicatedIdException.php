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

class DuplicatedIdException extends ComhonException {
	
	/**
	 * @param string $parameterName
	 */
	public function __construct($id) {
		parent::__construct("Duplicated id '$id'", ConstantException::DUPLICATED_ID_EXCEPTION);
	}
	
}