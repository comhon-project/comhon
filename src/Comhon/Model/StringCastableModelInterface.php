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
	 * @return mixed
	 */
	public function castValue($value);
	
}