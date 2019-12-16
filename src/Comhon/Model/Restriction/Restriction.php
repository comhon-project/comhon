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

abstract class Restriction {
	
	/**
	 * verify if specified value satisfy restriction
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function satisfy($value);
	
	/**
	 * verify if specified restriction is equal to $this
	 * 
	 * @param Restriction $restriction
	 * @return boolean
	 */
	abstract public function isEqual(Restriction $restriction);
	
	/**
	 * verify if specified model can use this restriction
	 * 
	 * @param \Comhon\Model\AbstractModel $model
	 * @return boolean
	 */
	abstract public function isAllowedModel(AbstractModel $model);
	
	/**
	 * stringify restriction and value
	 * 
	 * @param mixed $value
	 * @return string
	 */
	abstract public function toMessage($value);
	
	/**
	 * stringify restriction
	 *
	 * @return string
	 */
	abstract public function toString();
	
	/**
	 * verify if restrictions given are equals (compare restrictions stored on same keys)
	 *
	 * @param Restriction[] $firstRestrictions
	 * @param Restriction[] $secondRestrictions
	 * @return boolean if true, all restrictions are equals
	 */
	public static function compare(array $firstRestrictions, array $secondRestrictions) {
		if (count($firstRestrictions) !== count($secondRestrictions)) {
			return false;
		}
		foreach ($firstRestrictions as $key => $restriction) {
			if (!isset($secondRestrictions[$key]) || !$restriction->isEqual($secondRestrictions[$key])) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * find first restriction that satisfy given value
	 *
	 * @param Restriction[] $restrictions
	 * @param mixed $value
	 * @return Restriction|null null if all restrictions are satisfied
	 */
	public static function getFirstNotSatisifed(array $restrictions, $value) {
		foreach ($restrictions as $restriction) {
			if (!$restriction->satisfy($value)) {
				return $restriction;
			}
		}
		return null;
	}
	
}