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

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\Model;

class NoIdPropertyException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 */
	public function __construct(Model $model) {
		$message = "model '{$model->getName()}' doesn't have id property";
		parent::__construct($message, ConstantException::NO_ID_PROPERTY_EXCEPTION);
	}
	
}