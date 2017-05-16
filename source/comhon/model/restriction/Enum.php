<?php
namespace comhon\model\restriction;

use comhon\model\Model;

class Enum implements Restriction {
	
	/** @var mixed */
	private $mEnum = [];
	
	/**
	 * 
	 * @param string[]|integer[]|float[] $pEnum
	 */
	public function __construct(array $pEnum) {
		foreach ($pEnum as $lValue) {
			if (is_float($lValue)) {
				$this->mEnum[(string) $lValue] = null;
			} else {
				$this->mEnum[$lValue] = null;
			}
		}
	}
	
	public function satisfy($pValue) {
		return array_key_exists(is_float($pValue) ? (string) $pValue : $pValue, $this->mEnum);
	}
	
}