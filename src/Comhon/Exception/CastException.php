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

class CastException extends \Exception {
	
	public function __construct(Model $pSourceModel, Model $pDestModel) {
		$lMessage = "Cannot cast object, '{$pSourceModel->getName()}' is not inherited from '{$pDestModel->getName()}'";
		parent::__construct($lMessage, ConstantException::CAST_EXCEPTION);
	}
	
}