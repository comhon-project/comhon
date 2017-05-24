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
	 * @param string $regex
	 */
	public function __construct($name) {
		$this->regex = RegexCollection::getInstance()->getRegex($name);
	}
	
	/**
	 *
	 * @param string $value
	 */
	public function satisfy($value) {
		return preg_match($this->regex, $value) === 1;
	}
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Regex $restriction
	 */
	public function isEqual(Restriction $restriction) {
		return $this === $restriction || (($restriction instanceof Regex) && $this->regex === $restriction->regex);
	}
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $model
	 */
	public function isAllowedModel(Model $model) {
		return $model instanceof ModelString;
	}
	
	/**
	 * stringify restriction and value
	 * @param mixed $value
	 */
	public function toString($value) {
		if (!is_string($value)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Regex must be an instance of string, instance of $class given";
		}
		return $value . ($this->satisfy($value) ? ' ' : ' doesn\'t ')
			. 'satisfy regex ' . $this->regex;
	}
	
}