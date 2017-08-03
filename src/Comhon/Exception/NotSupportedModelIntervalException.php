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

class NotSupportedModelIntervalException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Model $model
	 */
	public function __construct(Model $model) {
		parent::__construct(
			"interval cannot be defined on model '{$model->getName()}'", 
			ConstantException::NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION
		);
	}
	
}