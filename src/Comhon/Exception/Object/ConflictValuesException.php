<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Object;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Model\Model;

class ConflictValuesException extends ComhonException {
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string[] $propertiesNames
	 */
	public function __construct(Model $model, array $propertiesNames) {
		$message = "properties values " . json_encode($propertiesNames)
			." cannot coexist for model '{$model->getName()}'";
		parent::__construct($message, ConstantException::CONFLICT_VALUES_EXCEPTION);
	}
	
}