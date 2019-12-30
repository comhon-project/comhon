<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Model;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class DependsPropertiesException extends ComhonException {
	
	/**
	 * $propertyOne depends on $propertyTwo
	 * 
	 * @param string $propertyOne
	 * @param string $propertyTwo
	 */
	public function __construct($propertyOne, $propertyTwo) {
		$message = "property '$propertyOne' can't be set without property '$propertyTwo'";
		parent::__construct($message, ConstantException::DEPENDS_PROPERTIES_EXCEPTION);
	}
	
}