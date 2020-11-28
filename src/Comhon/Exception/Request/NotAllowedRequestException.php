<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Request;

use Comhon\Model\AbstractModel;
use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class NotAllowedRequestException extends ComhonException {
	
	const SIMPLE_REQUEST = 'simple';
	const INTERMEDIATE_REQUEST = 'intermediate';
	const ADVANCED_REQUEST = 'advanced';
	
	/**
	 * @param \Comhon\Model\AbstractModel $model
	 * @param string[] $requestTypes types not allowed
	 */
	public function __construct(AbstractModel $model, $requestTypes = []) {
		$messagePart = empty($requestTypes) ? '' : implode(' or ', $requestTypes) . ' ';
		$message = "{$messagePart}request not allowed for model '{$model->getName()}'";
		parent::__construct($message, ConstantException::NOT_ALLOWED_REQUEST_EXCEPTION);
	}
	
}