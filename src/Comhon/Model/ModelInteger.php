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
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Model\CastStringException;

class ModelInteger extends SimpleModel implements StringCastableModelInterface {
	
	/** @var string */
	const ID = 'integer';
	
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
	 * @see \Comhon\Model\SimpleModel::_importScalarValue()
	 */
	protected function _importScalarValue($value, Interfacer $interfacer) {
		return $this->castValue($value);
	}
	
	/**
	 * cast value to integer
	 *
	 * @param string $value
	 * @param string $property if value belong to a property, permit to be more specific if an exception is thrown
	 * @return integer
	 */
	public function castValue($value, $property = null) {
		if (is_integer($value)) {
			return $value;
		}
		if (!ctype_digit($value)) {
			throw new CastStringException($value, 'integer', $property);
		}
		return (integer) $value;
	}
	
	/**
	 * verify if value is an integer
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!is_integer($value)) {
			throw new UnexpectedValueTypeException($value, 'integer');
		}
		return true;
	}
}