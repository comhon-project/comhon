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

class ModelString extends SimpleModel {
	
	/** @var string */
	const ID = 'string';
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\SimpleModel::_initializeModelName()
	 */
	protected function _initializeModelName() {
		$this->modelName = self::ID;
	}
	
	/**
	 * cast value to string
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function castValue($value) {
		return (string) $value;
	}
	
	/**
	 * verify if value is a string
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		if (!is_string($value)) {
			$nodes = debug_backtrace();
			$class = gettype($value) == 'object' ? get_class($value): gettype($value);
			throw new \Exception("Argument passed to {$nodes[0]['class']}::{$nodes[0]['function']}() must be a string, instance of $class given, called in {$nodes[0]['file']} on line {$nodes[0]['line']} and defined in {$nodes[0]['file']}");
		}
		return true;
	}
	
}