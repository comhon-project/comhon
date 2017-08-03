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

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\Property\Property;

class IncompatibleLiteralSerializationException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Model\Property\Property $property
	 */
	public function __construct(Property $property) {
		$message = "literal (with property '{$property->getName()}') serialization incompatible with requested model serialization";
		parent::__construct($message, ConstantException::INCOMPATIBLE_LITERAL_SERIALIZATION_EXCEPTION);
	}
	
}