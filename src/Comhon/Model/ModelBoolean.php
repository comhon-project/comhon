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

class ModelBoolean extends SimpleModel implements StringCastableModelInterface {
	
	/** @var string */
	const ID = 'boolean';
	
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
	 * @see \Comhon\Model\SimpleModel::exportSimple()
	 * 
	 * @return boolean|null
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			return $value ? '1' : '0';
		}
		return $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::importSimple()
	 * 
	 * @return boolean|null
	 */
	public function importSimple($value, Interfacer $interfacer, $applyCast = true) {
		if ($interfacer->isNullValue($value)) {
			return null;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToBoolean($value);
		} elseif ($applyCast && $interfacer->isStringifiedValues()) {
			$value = $this->castValue($value);
		}
		return $value;
	}
	
	/**
	 * cast value to boolean
	 * 
	 * @param string $value
	 * @return boolean
	 */
	public function castValue($value) {
		return (boolean) $value;
	}
	
	/**
	 * verify if value is a boolean
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!is_bool($value)) {
			throw new UnexpectedValueTypeException($value, 'boolean');
		}
		return true;
	}
	
}