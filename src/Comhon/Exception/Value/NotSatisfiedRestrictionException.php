<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Value;

use Comhon\Exception\ConstantException;
use Comhon\Model\Restriction\Restriction;

class NotSatisfiedRestrictionException extends UnexpectedValueTypeException {
	
	/**
	 * @var mixed
	 */
	private $value;
	
	/**
	 * @var \Comhon\Model\Restriction\Restriction
	 */
	private $restriction;
	
	/**
	 * @var integer
	 */
	private $increment;
	
	/**
	 * @param mixed $value
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 * @param integer $increment
	 */
	public function __construct($value, Restriction $restriction, $increment = 0) {
		$this->value = $value;
		$this->restriction = $restriction;
		$this->increment = $increment;
		$message = $restriction->toMessage($value, $increment);
		$this->message = $message;
		$this->code = ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION;
	}
	
	/**
	 * get value
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * get restriction
	 *
	 * @return \Comhon\Model\Restriction\Restriction
	 */
	public function getRestriction() {
		return $this->restriction;
	}
	
	/**
	 * get increment
	 *
	 * @return integer
	 */
	public function getIncrement() {
		return $this->increment;
	}
	
}