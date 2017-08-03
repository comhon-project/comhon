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

class AlreadyUsedModelNameException extends ComhonException {
	
	/**
	 * @param string $modelName
	 */
	public function __construct($modelName) {
		parent::__construct("model $modelName already used", ConstantException::ALREADY_USED_MODEL_NAME_EXCEPTION);
	}
	
}