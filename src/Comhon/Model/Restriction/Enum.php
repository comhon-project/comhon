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

use Comhon\Model\Model;
use Comhon\Model\ModelString;

class Enum implements Restriction {
	
	/** @var array */
	private $enum = [];
	
	/**
	 * 
	 * @param string[]|integer[]|float[] $enum
	 */
	public function __construct(array $enum) {
		foreach ($enum as $value) {
			if (is_float($value)) {
				$this->enum[(string) $value] = null;
			} else {
				$this->enum[$value] = null;
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		if (is_float($value)) {
			return array_key_exists((string) $value, $this->enum);
		} elseif (is_string($value) || is_integer($value)) {
			return array_key_exists($value, $this->enum);
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		if ($this === $restriction) {
			return true;
		}
		if (!($restriction instanceof Enum)) {
			return false;
		}
		if (count($this->enum) !== count($restriction->enum)) {
			return false;
		}
		foreach ($this->enum as $key => $value) {
			if (!array_key_exists($key, $restriction->enum)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(Model $model) {
		return ($model instanceof ModelInteger)
		|| ($model instanceof ModelString)
		|| ($model instanceof ModelFloat);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toMessage($value) {
		if (!is_float($value) && !is_integer($value) && !is_string($value)) {
			$class = gettype($value) == 'object' ? get_class($value) : gettype($value);
			return "Value passed to Enum must be an integer, float or string, instance of $class given";
		}
		return $value . ' is' . ($this->satisfy($value) ? ' ' : ' not ')
			. 'in enumeration ' . json_encode(array_keys($this->enum));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return json_encode(array_keys($this->enum));
	}
	
}