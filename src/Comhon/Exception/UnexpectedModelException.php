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

class UnexpectedModelException extends ComhonException {
	
	/**
	 * @param \Comhon\Model\Model $expectedModel
	 * @param \Comhon\Model\Model $actualModel
	 */
	public function __construct(Model $expectedModel, Model $actualModel) {
		$message = "model must be a '{$expectedModel->getName()}', model '{$actualModel->getName()}' given";
		parent::__construct($message, ConstantException::UNEXPECTED_MODEL_EXCEPTION);
	}
	
}