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

class UniqueModelNameException extends ComhonException {
	
	/**
	 * 
	 * @param string $modelName
	 */
	public function __construct($modelName) {
		$message = "$modelName already used, model name must be unique";
		parent::__construct($message, ConstantException::UNIQUE_MODEL_NAME_EXCEPTION);
	}
	
}