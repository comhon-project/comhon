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
	 * 
	 * @param mixed $value
	 */
	public function castValueToString($value);
	
	/**
	 *
	 * @param mixed $value
	 */
	public function castValueToInteger($value);
	
	/**
	 *
	 * @param mixed $value
	 */
	public function castValueToFloat($value);
	
	/**
	 *
	 * @param mixed $value
	 */
	public function castValueToBoolean($value);
	
}
