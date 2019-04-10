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

class ConfigFileNotFoundException extends ComhonException {
	
	/**
	 * 
	 * @param string $name
	 * @param string $nature
	 * @param string $path
	 */
	public function __construct($name, $nature, $path) {
		parent::__construct("'$name' $nature '$path' doesn't exist or not readable", ConstantException::CONFIG_NOT_FOUND_EXCEPTION);
	}
	
}