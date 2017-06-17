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

interface NoScalarTypedInterfacer{
	
	/**
	 * cast value to string
	 * 
	 * @param mixed $value
	 */
	public function castValueToString($value);
	
	/**
	 * cast value to integer
	 *
	 * @param mixed $value
	 */
	public function castValueToInteger($value);
	
	/**
	 * cast value to float
	 *
	 * @param mixed $value
	 */
	public function castValueToFloat($value);
	
	/**
	 * cast value to boolean
	 *
	 * @param mixed $value
	 */
	public function castValueToBoolean($value);
	
}
