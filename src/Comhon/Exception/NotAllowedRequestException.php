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

class NotAllowedRequestException extends ComhonException {
	
	const SIMPLE_REQUEST = 'simple';
	const INTERMEDIATE_REQUEST = 'intermediate';
	const COMPLEXE_REQUEST = 'complex';
	
	/**
	 * @param \Comhon\Model\Model $model
	 * @param string[] $requestTypes types not allowed
	 */
	public function __construct(Model $model, $requestTypes = []) {
		$messagePart = empty($requestTypes) ? '' : implode(' or ', $requestTypes) . ' ';
		$message = "{$messagePart}request not allowed for model '{$model->getName()}'";
		parent::__construct($message, ConstantException::NOT_ALLOWED_REQUEST_EXCEPTION);
	}
	
}