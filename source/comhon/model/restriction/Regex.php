<?php
namespace comhon\model\restriction;

use comhon\model\ModelString;
use comhon\model\Model;

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
		return $this === $pRestriction || $this->mRegex == $pRestriction->mRegex;
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