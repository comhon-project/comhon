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
use Comhon\Model\modelArray;
use Comhon\Object\ComhonArray;

class UnexpectedRestrictedArrayException extends UnexpectedValueTypeException {
	
	private $modelArray;
	
	/**
	 * 
	 * @param \Comhon\Object\ComhonArray $objectArray
	 * @param \Comhon\Model\ModelArray $modelArray
	 */
	public function __construct(ComhonArray $objectArray, ModelArray $modelArray) {
		$this->modelArray = $modelArray;
		$expectedRestriction = '';
		foreach ($modelArray->getArrayRestrictions() as $restriction) {
			$expectedRestriction .= ' - ' . $restriction->toString() . '(on comhon array)' . PHP_EOL;
		}
		foreach ($modelArray->getElementRestrictions() as $restriction) {
			$expectedRestriction .= ' - ' . $restriction->toString() . '(on elements)' . PHP_EOL;
		}
		$actualRestriction = '';
		if ($objectArray->getModel() instanceof modelArray ) {
			foreach ($objectArray->getModel()->getArrayRestrictions() as $restriction) {
				$actualRestriction .= ' - ' . $restriction->toString() . '(on comhon array)' . PHP_EOL;
			}
			foreach ($objectArray->getModel()->getElementRestrictions() as $restriction) {
				$actualRestriction .= ' - ' . $restriction->toString() . '(on elements)' . PHP_EOL;
			}
		}
		$class = get_class($objectArray);
		
		$this->message = "value $class must have restrictions :". PHP_EOL 
			. $expectedRestriction
			. "restrictions given : " . PHP_EOL 
			. $actualRestriction;
		$this->code = ConstantException::UNEXPECTED_VALUE_TYPE_EXCEPTION;
	}
	
	public function getModelArray() {
		return $this->modelArray;
	}
	
}