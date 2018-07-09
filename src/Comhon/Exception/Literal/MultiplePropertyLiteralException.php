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
use Comhon\Model\Property\MultipleForeignProperty;

class MultiplePropertyLiteralException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Property\MultipleForeignProperty $property
	 */
	public function __construct(MultipleForeignProperty $property) {
		$message = 'property \''.$property->getName().'\'not allowed, having-literal cannot reference multiple foreign property.';
		parent::__construct($message, ConstantException::MULTIPLE_PROPERTY_LITERAL_EXCEPTION);
	}
	
}