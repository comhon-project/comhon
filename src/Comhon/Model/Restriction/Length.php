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
use Comhon\Model\ModelString;

class Length extends Interval {
	
	public function __construct($interval) {
		parent::__construct($interval, ModelManager::getInstance()->getInstanceModel('integer'));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		return is_string($value) && parent::satisfy(strlen($value));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return parent::isEqual($restriction) && ($restriction instanceof Length);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(AbstractModel $model) {
		return $model instanceof ModelString;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toMessage()
	 */
	public function toMessage($value) {
		if (!is_string($value)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Length must be a string, instance of $class given";
		}
		
		return 'length ' . strlen($value) . ' of given string'
			. ' is' . ($this->satisfy($value) ? ' ' : ' not ')
			. 'in length range '
			. ($this->_isLeftClosed() ? '[' : ']')
			. $this->_getLeftEndPoint()
			. ','
			. $this->_getRightEndPoint()
			. ($this->_isRightClosed() ? ']' : '[');
	}
	
}