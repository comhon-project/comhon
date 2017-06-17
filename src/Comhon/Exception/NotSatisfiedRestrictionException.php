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

class NotSatisfiedRestrictionException extends \Exception {
	
	/**
	 * @param mixed $value
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 */
	public function __construct($value, Restriction $restriction) {
		$message = $restriction->toString($value);
		parent::__construct($message, ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
	}
	
}