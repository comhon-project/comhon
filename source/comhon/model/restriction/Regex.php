<?php
namespace comhon\model\restriction;

class Regex implements Restriction {
	
	/** @var mixed */
	private $mRegex;
	
	/**
	 * 
	 * @param string $pRegex
	 */
	public function __construct($pName) {
		$this->mRegex = RegexCollection::getInstance()->getRegex($pName);
	}
	
	public function satisfy($pValue) {
		return preg_match($this->mRegex, $pValue) === 1;
	}
	
}