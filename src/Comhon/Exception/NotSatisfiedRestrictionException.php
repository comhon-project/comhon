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
	
	public function __construct($pValue, Restriction $pRestriction) {
		$lMessage = $pRestriction->toString($pValue);
		parent::__construct($lMessage, ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
	}
	
}