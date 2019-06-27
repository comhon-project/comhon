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

use Comhon\Model\AbstractModel;

interface Restriction {
	
	/**
	 * verify if specified value satisfy restriction
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function satisfy($value);
	
	/**
	 * verify if specified restriction is equal to $this
	 * 
	 * @param Restriction $restriction
	 * @return boolean
	 */
	public function isEqual(Restriction $restriction);
	
	/**
	 * verify if specified model can use this restriction
	 * 
	 * @param \Comhon\Model\AbstractModel $model
	 * @return boolean
	 */
	public function isAllowedModel(AbstractModel $model);
	
	/**
	 * stringify restriction and value
	 * 
	 * @param mixed $value
	 * @return string
	 */
	public function toMessage($value);
	
	/**
	 * stringify restriction
	 *
	 * @return string
	 */
	public function toString();
	
}