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

use Comhon\Model\Model;

class UndefinedPropertyException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Model $model
	 * @param string $propertyName
	 */
	public function __construct(Model $model, $propertyName) {
		$message = "Undefined property '$propertyName' for model '{$model->getName()}'";
		parent::__construct($message, ConstantException::UNDEFINED_PROPERTY_EXCEPTION);
	}
	
}