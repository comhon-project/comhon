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

class PropertyException extends \Exception {
	
	public function __construct($pModel, $pPropertyName) {
		$lMessage = "Unknown property '$pPropertyName' for model '{$pModel->getName()}'";
		parent::__construct($lMessage, ConstantException::PROPERTY_EXCEPTION);
	}
	
}