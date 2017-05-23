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
	 * @param mixed $pValue
	 */
	public function castValueToString($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToInteger($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToFloat($pValue);
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function castValueToBoolean($pValue);
	
}
