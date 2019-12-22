<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Request;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\Property\Property;
use Comhon\Object\UniqueObject;
use Comhon\Model\Model;

class NotAllowedLiteralException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Model\Property\Property $property
	 * @param \Comhon\Object\UniqueObject $literal
	 */
	public function __construct(Model $model, Property $property, UniqueObject $literal) {
		$message = "literal '{$literal->getModel()->getName()}' not allowed on property '{$property->getName()}' of model '{$model->getName()}'."
		. " must be one of [". implode(', ', $property->getAllowedLiterals()) . ']';
		parent::__construct($message, ConstantException::NOT_ALLOWED_LITERAL_EXCEPTION);
	}
	
}