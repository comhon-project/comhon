<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Literal;

use Comhon\Exception\ConstantException;
use Comhon\Exception\ComhonException;

class LiteralPropertyAggregationException extends ComhonException {
	
	/**
	 * @param string $propertyName
	 */
	public function __construct($propertyName) {
		$message = "literal cannot contain aggregation property '$propertyName' except in queue node";
		parent::__construct($message, ConstantException::LITERAL_PROPERTY_AGGREGATION_EXCEPTION);
	}
	
}