<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Restriction;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\AbstractModel;

class NotSupportedModelIntervalException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\AbstractModel $model
	 */
	public function __construct(AbstractModel $model) {
		parent::__construct(
			"interval cannot be defined on model '{$model->getName()}'", 
			ConstantException::NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION
		);
	}
	
}