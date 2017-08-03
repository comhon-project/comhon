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

use Comhon\Exception\ConstantException;
use Comhon\Exception\ComhonException;

class PropertyVisibilityException extends ComhonException {
	
	/**
	 * @param string $propertyName
	 */
	public function __construct($propertyName) {
		$message = "cannot use private property '$propertyName' in public context";
		parent::__construct($message, ConstantException::PROPERTY_VISIBILITY_EXCEPTION);
	}
	
}