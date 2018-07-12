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
	 * @see \Comhon\Model\SimpleModel::importSimple()
	 */
	public function importSimple($value, Interfacer $interfacer, $applyCast = true) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToInteger($value);
		} elseif ($applyCast && $interfacer->isStringifiedValues()) {
			$value = $this->castValue($value);
		}
		return $value;
	}
	
	/**
	 * cast value to integer
	 *
	 * @param string $value
	 * @return integer
	 */
	public function castValue($value) {
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