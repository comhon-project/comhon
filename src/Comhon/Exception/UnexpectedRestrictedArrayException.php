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

use Comhon\Model\Restriction\Restriction;
use Comhon\Object\ObjectArray;
use Comhon\Model\ModelRestrictedArray;

class UnexpectedRestrictedArrayException extends UnexpectedValueTypeException {
	
	/**
	 * @param mixed $value
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 */
	public function __construct(ObjectArray $objectArray, ModelRestrictedArray $modelRestrictedArray) {
		$expectedRestriction = $modelRestrictedArray->getStringifiedRestriction();
		$actualRestriction =  $objectArray->getModel() instanceof ModelRestrictedArray 
			? $objectArray->getModel()->getStringifiedRestriction()
			: 'NULL';
		$class = get_class($objectArray);
		
		$this->message = "value $class must have a restriction '$expectedRestriction', $actualRestriction given";
		$this->code = ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION;
	}
	
}