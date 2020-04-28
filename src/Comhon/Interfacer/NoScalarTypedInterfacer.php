<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Interfacer;

abstract class NoScalarTypedInterfacer extends Interfacer {
	
	/**
	 * verify if interfaced simple values are stringified and must be casted during import
	 *
	 * @param boolean $boolean
	 */
	public function isStringifiedValues() {
		return false;
	}
	
	/**
	 * verify if interfaced object has typed scalar values (int, float, string...).
	 *
	 * @return boolean
	 */
	public function hasScalarTypedValues() {
		return false;
	}
	
	/**
	 * cast value to string and return it
	 * 
	 * @param mixed $value
	 * @return string
	 */
	abstract public function castValueToString($value);
	
	/**
	 * cast value to integer and return it
	 *
	 * @param mixed $value
	 * @return integer
	 */
	abstract public function castValueToInteger($value);
	
	/**
	 * cast value to float and return it
	 *
	 * @param mixed $value
	 * @return float
	 */
	abstract public function castValueToFloat($value);
	
	/**
	 * cast value to boolean and return it
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function castValueToBoolean($value);
	
}
