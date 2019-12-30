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

use Comhon\Model\ModelString;
use Comhon\Model\AbstractModel;

class Range extends Regex {
	
	/**
	 * 
	 * @param string $regex regular expression
	 */
	public function __construct() {
		parent::__construct('/^\\d+-\\d+$/');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		if (!parent::satisfy($value)) {
			return false;
		}
		list($first, $last) = explode('-', $value);
		
		return (1 + $last - $first) > 0;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return $restriction instanceof Regex;
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
			return "Value passed to Regex must be a string, instance of $class given";
		}
		return $value . ($this->satisfy($value) ? ' ' : ' doesn\'t ')
			. 'satisfy range format \'x-y\' where x and y are integer and x<=y';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return 'x-y';
	}
	
}