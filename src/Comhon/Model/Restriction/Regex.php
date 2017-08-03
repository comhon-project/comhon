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
use Comhon\Model\Model;

class Regex implements Restriction {
	
	/** @var string */
	private $regex;
	
	/**
	 * 
	 * @param string $name the name of a regex
	 */
	public function __construct($name) {
		$this->regex = RegexCollection::getInstance()->getRegex($name);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		return preg_match($this->regex, $value) === 1;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return $this === $restriction || (($restriction instanceof Regex) && $this->regex === $restriction->regex);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(Model $model) {
		return $model instanceof ModelString;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toMessage($value) {
		if (!is_string($value)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Regex must be a string, instance of $class given";
		}
		return $value . ($this->satisfy($value) ? ' ' : ' doesn\'t ')
			. 'satisfy regex ' . $this->regex;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return $this->regex;
	}
	
}