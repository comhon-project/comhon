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
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;

class ModelBoolean extends SimpleModel {
	
	const ID = 'boolean';
	
	protected function _init() {
		$this->modelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof XMLInterfacer) {
			return $value ? 1 : 0;
		}
		return $value;
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @return boolean|null
	 */
	public function importSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToBoolean($value);
		}
		return $value;
	}
	
	public function castValue($value) {
		return (boolean) $value;
	}
	
	public function verifValue($value) {
		if (!is_bool($value)) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be a boolean, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
}