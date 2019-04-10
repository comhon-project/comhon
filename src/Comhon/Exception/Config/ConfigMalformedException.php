<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Config;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class ConfigMalformedException extends ComhonException {
	
	/**
	 * 
	 * @param string $path
	 */
	public function __construct($path) {
		parent::__construct("config file '$path' is malformed", ConstantException::CONFIG_MALFORMED_EXCEPTION);
	}
	
}