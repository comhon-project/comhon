<?php
namespace comhon\model\restriction;

use comhon\model\Model;
use comhon\model\ModelString;

class Enum implements Restriction {
	
	/** @var array */
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
		if (is_float($pValue)) {
			return array_key_exists((string) $pValue, $this->mEnum);
		} elseif (is_string($pValue) || is_integer($pValue)) {
			return array_key_exists($pValue, $this->mEnum);
		}
		return false;
	}
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Enum $pRestriction
	 */
	public function isEqual(Restriction $pRestriction) {
		if ($this === $pRestriction) {
			return true;
		}
		if (!($pRestriction instanceof Enum)) {
			return false;
		}
		if (count($this->mEnum) !== count($pRestriction->mEnum)) {
			return false;
		}
		foreach ($this->mEnum as $lKey => $lValue) {
			if (!array_key_exists($lKey, $pRestriction->mEnum)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $pModel
	 */
	public function isAllowedModel(Model $pModel) {
		return ($pModel instanceof ModelInteger)
		|| ($pModel instanceof ModelString)
		|| ($pModel instanceof ModelFloat);
	}
	
	/**
	 * stringify restriction and value
	 * @param mixed $pValue
	 */
	public function toString($pValue) {
		if (!is_float($pValue) && !is_integer($pValue) && !is_string($pValue)) {
			$lClass = gettype($pValue) == 'object' ? get_class($pValue) : gettype($pValue);
			return "Value passed to Enum must be an instance of integer, float or string, instance of $lClass given";
		}
		return $pValue . ' is' . ($this->satisfy($pValue) ? ' ' : ' not ')
			. 'in enumeration ' . json_encode(array_keys($this->mEnum));
	}
	
}