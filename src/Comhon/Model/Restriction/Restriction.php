<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Restriction;

use Comhon\Model\Model;

interface Restriction {
	
	/**
	 *
	 * @param mixed $value
	 */
	public function satisfy($value);
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Restriction $restriction
	 */
	public function isEqual(Restriction $restriction);
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $model
	 */
	public function isAllowedModel(Model $model);
	
	/**
	 * stringify restriction and value
	 * @param mixed $value
	 */
	public function toString($value);
	
}