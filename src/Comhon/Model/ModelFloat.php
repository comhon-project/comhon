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
use Comhon\Exception\UnexpectedValueTypeException;

class ModelFloat extends SimpleModel implements StringCastableModelInterface {
	
	/** @var string */
	const ID = 'float';
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::_initializeModelName()
	 */
	protected function _initializeModelName() {
		$this->modelName = self::ID;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::importSimple()
	 */
	public function importSimple($value, Interfacer $interfacer, $applyCast = true) {
		if ($interfacer->isNullValue($value)) {
			return null;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToFloat($value);
		} elseif ($applyCast && $interfacer->isStringifiedValues()) {
			$value = $this->castValue($value);
		}
		return $value;
	}
	
	/**
	 * cast value to float
	 *
	 * @param string $value
	 * @return float
	 */
	public function castValue($value) {
		return (float) $value;
	}
	
	/**
	 * verify if value is a float (or an integer)
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!(is_float($value) || is_integer($value))) {
			throw new UnexpectedValueTypeException($value, 'double');
		}
		return true;
	}
	
}