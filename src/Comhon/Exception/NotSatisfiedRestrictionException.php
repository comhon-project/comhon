<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception;

use Comhon\Model\Restriction\Restriction;

class NotSatisfiedRestrictionException extends UnexpectedValueTypeException {
	
	/**
	 * @var \Comhon\Model\Restriction\Restriction
	 */
	private $restriction;
	
	/**
	 * @param mixed $value
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 */
	public function __construct($value, Restriction $restriction) {
		$this->restriction = $restriction;
		$message = $restriction->toMessage($value);
		$this->message = $message;
		$this->code = ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION;
	}
	
	/**
	 * get restriction
	 * 
	 * @return \Comhon\Model\Restriction\Restriction
	 */
	public function getRestriction() {
		return $this->restriction;
	}
	
}