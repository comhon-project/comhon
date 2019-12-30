<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

interface StringCastableModelInterface {
	
	/**
	 * cast string value in appropriate type according instanciated simple model
	 * 
	 * @param string $value
	 * @param string $property if value belong to a property, permit to be more specific if an exception is thrown
	 * @return mixed
	 */
	public function castValue($value, $property = null);
	
}