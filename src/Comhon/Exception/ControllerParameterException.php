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

class ControllerParameterException extends \Exception {
	
	public function __construct($parameterName = null) {
		$message = is_null($parameterName) ? 'Bad parameters definition : must be an array or null'
											 : "Missing parameter : '$parameterName' must be specified";
		
		parent::__construct($message, ConstantException::CONTROLLER_PARAMETER_EXCEPTION);
	}
	
}