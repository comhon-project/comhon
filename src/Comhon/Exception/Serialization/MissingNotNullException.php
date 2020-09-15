<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Serialization;

use Comhon\Exception\ConstantException;
use Comhon\Model\Model;
use Comhon\Exception\ComhonException;

class MissingNotNullException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $propertyName
	 */
	public function __construct(Model $model, $propertyName) {
		$message = "property '$propertyName' of model '{$model->getName()}' is not set and cannot be serialized with null value. "
			.'property should probably be set as required';
		parent::__construct($message, ConstantException::MISSING_NOT_NULL_EXCEPTION);
	}
	
}