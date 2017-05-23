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
	private $mRegex;
	
	/**
	 * 
	 * @param string $pRegex
	 */
	public function __construct($pName) {
		$this->mRegex = RegexCollection::getInstance()->getRegex($pName);
	}
	
	/**
	 *
	 * @param string $pValue
	 */
	public function satisfy($pValue) {
		return preg_match($this->mRegex, $pValue) === 1;
	}
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Regex $pRestriction
	 */
	public function isEqual(Restriction $pRestriction) {
		return $this === $pRestriction || (($pRestriction instanceof Regex) && $this->mRegex === $pRestriction->mRegex);
	}
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $pModel
	 */
	public function isAllowedModel(Model $pModel) {
		return $pModel instanceof ModelString;
	}
	
	/**
	 * stringify restriction and value
	 * @param mixed $pValue
	 */
	public function toString($pValue) {
		if (!is_string($pValue)) {
			$lClass = gettype($pValue) == 'object' ? get_class($pValue) : gettype($pValue);
			return "Value passed to Regex must be an instance of string, instance of $lClass given";
		}
		return $pValue . ($this->satisfy($pValue) ? ' ' : ' doesn\'t ')
			. 'satisfy regex ' . $this->mRegex;
	}
	
}