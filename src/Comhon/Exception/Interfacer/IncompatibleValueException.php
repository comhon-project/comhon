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
use Comhon\Interfacer\Interfacer;

class IncompatibleValueException extends ComhonException {
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	public function __construct($interfacedObject, Interfacer $interfacer) {
		$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
		$message = 'value ('.$type.') imcompatible with interfacer ('.get_class($interfacer).')';
		parent::__construct($message);
	}
	
}