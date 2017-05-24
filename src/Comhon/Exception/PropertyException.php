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

class PropertyException extends \Exception {
	
	public function __construct($model, $propertyName) {
		$message = "Unknown property '$propertyName' for model '{$model->getName()}'";
		parent::__construct($message, ConstantException::PROPERTY_EXCEPTION);
	}
	
}