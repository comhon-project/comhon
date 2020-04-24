<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Interfacer;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class AbstractObjectExportException extends ComhonException {
	
	/**
	 * @param string $id
	 */
	public function __construct($modelName) {
		parent::__construct(
			"model '$modelName' is abstract. abstract model can't be exported",
			ConstantException::ABSTRACT_OBJECT_EXPORT_EXCEPTION
		);
	}
	
}