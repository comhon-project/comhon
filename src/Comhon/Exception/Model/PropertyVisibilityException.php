<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Model;

use Comhon\Exception\ConstantException;
use Comhon\Exception\ComhonException;
use Comhon\Model\Property\Property;
use Comhon\Model\Model;

class PropertyVisibilityException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Property\Property $property
	 * @param \Comhon\Model\Model $model
	 */
	public function __construct(Property $property, Model $model) {
		$isIdMessage = $property->isId() ? ' id ' : ' ';
		$message = "cannot use private{$isIdMessage}property '{$property->getName()}' on model '{$model->getName()}' in public context";
		parent::__construct($message, ConstantException::PROPERTY_VISIBILITY_EXCEPTION);
	}
	
}