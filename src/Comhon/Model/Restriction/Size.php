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
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelArray;
use Comhon\Object\ComhonArray;

class Size extends Interval {
	
	public function __construct($interval) {
		parent::__construct($interval, ModelManager::getInstance()->getInstanceModel('integer'));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 * @param integer $increment
	 */
	public function satisfy($value, $increment = 0) {
		return ($value instanceof ComhonArray) && parent::satisfy($value->count() + $increment);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return parent::isEqual($restriction) && ($restriction instanceof Size);
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
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 * @param integer $increment
	 */
	public function toMessage($value, $increment = 0) {
		if (!($value instanceof ComhonArray)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Size must be an ComhonArray, instance of $class given";
		}
		
		return ($increment != 0 ? ('trying to modify comhon array from size ' . $value->count() . ' to ' . ($value->count() + $increment) . '. ') : '')
			. 'size '.($value->count() + $increment).' of given array'
			. ' is' . ($this->satisfy($value, $increment) ? ' ' : ' not ')
			. 'in size range '
			. ($this->isLeftClosed ? '[' : ']')
			. $this->leftEndPoint
			. ','
			. $this->rightEndPoint
			. ($this->isRightClosed ? ']' : '[');
	}
	
}