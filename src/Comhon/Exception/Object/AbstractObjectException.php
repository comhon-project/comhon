<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Object;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;
use Comhon\Object\UniqueObject;

class AbstractObjectException extends ComhonException {
	
	/**
	 * 
	 * @param UniqueObject $ComhonObject
	 */
	public function __construct(UniqueObject $ComhonObject) {
		$modelName = $ComhonObject->getModel()->getName();
		$message = $ComhonObject->getModel()->isAbstract() 
			? "model '$modelName' is abstract. Objects with abstract model cannot be flagged as loaded"
			: "error AbstractObjectException instanciated but model '$modelName' is not abstract";
		parent::__construct($message, ConstantException::ABSTRACT_OBJECT_EXCEPTION);
	}
	
}