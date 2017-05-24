<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;

class ModelFloat extends SimpleModel {
	
	const ID = 'float';
	
	protected function _init() {
		$this->modelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @return float|null
	 */
	public function importSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToFloat($value);
		}
		return $value;
	}
	
	public function castValue($value) {
		return (float) $value;
	}
	
	public function verifValue($value) {
		if (!(is_float($value) || is_integer($value))) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be a float or integer, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
}