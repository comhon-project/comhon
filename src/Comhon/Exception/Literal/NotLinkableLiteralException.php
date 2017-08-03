<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Literal;

use Comhon\Model\Model;
use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class NotLinkableLiteralException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Model $model
	 * @param \stdClass $literal
	 */
	public function __construct(Model $model, \stdClass $literal) {
		$message = "model '{$literal->model}' from literal ".json_encode($literal)." is not linked to requested model '{$model->getName()}' or doesn't have compatible serialization";
		parent::__construct($message, ConstantException::NOT_LINKABLE_LITERAL_EXCEPTION);
	}
	
}