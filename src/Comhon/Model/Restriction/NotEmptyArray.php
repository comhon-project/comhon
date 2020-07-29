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
use Comhon\Model\ModelArray;
use Comhon\Object\ComhonArray;

class NotEmptyArray extends Restriction {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 * @param integer $increment permit to verify if restriction is satisfied if add or remove one or several values on array
	 */
	public function satisfy($value, $increment = 0) {
		return ($value instanceof ComhonArray) ?  ($value->count() + $increment > 0) : !empty($value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return $this === $restriction || (($restriction instanceof NotEmptyArray));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(AbstractModel $model) {
		return $model instanceof ModelArray;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toMessage()
	 * @param integer $increment
	 */
	public function toMessage($value, $increment = 0) {
		return $this->satisfy($value, $increment)
			? 'value is not empty'
			: ($increment == -1 
				? 'trying to modify comhon array and make it empty, value must be not empty' 
				: 'value is empty, value must be not empty');
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return 'Not empty';
	}
	
}