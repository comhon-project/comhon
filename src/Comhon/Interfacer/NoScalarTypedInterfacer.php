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

use Comhon\Exception\Model\CastStringException;

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
	 * cast value to integer and return it
	 *
	 * @param mixed $value
	 * @return integer
	 */
	public function castValueToInteger($value) {
		if (is_integer($value)) {
			return $value;
		}
		if (!ctype_digit($value)) {
			throw new CastStringException($value, 'integer');
		}
		return (integer) $value;
	}
	
	/**
	 * cast value to float and return it
	 *
	 * @param mixed $value
	 * @return float
	 */
	public function castValueToFloat($value) {
		if (is_float($value)) {
			return $value;
		}
		if (!is_numeric($value)) {
			throw new CastStringException($value, 'float');
		}
		return (float) $value;
	}
	
	/**
	 * cast value to boolean and return it
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function castValueToBoolean($value) {
		if (is_bool($value)) {
			return $value;
		}
		if ($value !== '0' && $value !== '1') {
			throw new CastStringException($value, ['0', '1']);
		}
		return $value === '1';
	}
	
}
