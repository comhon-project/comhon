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
	 * @param mixed $pValue
	 */
	public function satisfy($pValue);
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Restriction $pRestriction
	 */
	public function isEqual(Restriction $pRestriction);
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $pModel
	 */
	public function isAllowedModel(Model $pModel);
	
	/**
	 * stringify restriction and value
	 * @param mixed $pValue
	 */
	public function toString($pValue);
	
}