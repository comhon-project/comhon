<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Value;

use Comhon\Exception\ConstantException;
use Comhon\Object\ComhonArray;
use Comhon\Model\ModelRestrictedArray;

class UnexpectedRestrictedArrayException extends UnexpectedValueTypeException {
	
	private $modelRestrictedArray;
	
	/**
	 * @param mixed $value
	 * @param \Comhon\Model\Restriction\Restriction $restriction
	 */
	public function __construct(ComhonArray $objectArray, ModelRestrictedArray $modelRestrictedArray) {
		$this->modelRestrictedArray = $modelRestrictedArray;
		$expectedRestriction = '';
		foreach ($modelRestrictedArray->getRestrictions() as $restriction) {
			$expectedRestriction .= ' - ' . $restriction->toString() . PHP_EOL;
		}
		$actualRestriction = '';
		if ($objectArray->getModel() instanceof ModelRestrictedArray ) {
			foreach ($objectArray->getModel()->getRestrictions() as $restriction) {
				$actualRestriction .= ' - ' . $restriction->toString() . PHP_EOL;
			}
		}
		$class = get_class($objectArray);
		
		$this->message = "value $class must have restrictions :". PHP_EOL 
			. $expectedRestriction
			. "restrictions given : " . PHP_EOL 
			. $actualRestriction;
		$this->code = ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION;
	}
	
	public function getModelRestrictedArray() {
		return $this->modelRestrictedArray;
	}
	
}