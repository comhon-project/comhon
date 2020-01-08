<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Object;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class DependsValuesException extends ComhonException {
	
	/**
	 * $propertyOne depends on $propertyTwo
	 * 
	 * @param string $propertyOne
	 * @param string $propertyTwo
	 * @param boolean $unset
	 */
	public function __construct($propertyOne, $propertyTwo, $unset = false) {
		$message = $unset ? "property value '$propertyOne' can't be unset when property value '$propertyTwo' is set" 
			: "property value '$propertyOne' can't be set without property value '$propertyTwo'";
		parent::__construct($message, ConstantException::DEPENDS_VALUES_EXCEPTION);
	}
	
}