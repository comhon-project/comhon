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

use Comhon\Exception\CastStringException;

class AssocArrayNoScalarTypedInterfacer extends AssocArrayInterfacer implements NoScalarTypedInterfacer {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToString()
	 */
	public function castValueToString($value) {
		return $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToInteger()
	 */
	public function castValueToInteger($value) {
		if (!is_numeric($value)) {
			throw new CastStringException($value, 'numeric');
		}
		return (integer) $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToFloat()
	 */
	public function castValueToFloat($value) {
		if (!is_numeric($value)) {
			throw new CastStringException($value, 'numeric');
		}
		return (float) $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\NoScalarTypedInterfacer::castValueToBoolean()
	 */
	public function castValueToBoolean($value) {
		if ($value !== '0' && $value !== '1') {
			throw new CastStringException($value, ['0', '1']);
		}
		return $value === '1';
	}
	
}
