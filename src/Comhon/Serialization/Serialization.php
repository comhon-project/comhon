<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization;

use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;

final class Serialization {
	
	/** @var \Comhon\Object\UniqueObject */
	private $settings;
	
	/** @var string */
	private $serializationUnitClass;
	
	/** @var string */
	private $inheritanceKey;
	
	/** @var \Comhon\Serialization\SerializationUnit */
	private $serializationUnit;
	
	/** @var string[] */
	private $inheritanceValues;
	
	/**
	 * 
	 * @param UniqueObject $settings
	 * @param string $serializationUnitClass
	 * @param string $inheritanceKey
	 * @param string[] $inheritanceValues
	 */
	private function __construct(UniqueObject $settings = null, $serializationUnitClass = null, $inheritanceKey = null, $inheritanceValues = null) {
		$this->settings = $settings;
		$this->inheritanceKey = $inheritanceKey;
		
		if (is_null($serializationUnitClass)) {
			if (is_null($settings)) {
				throw new SerializationException('invalid parameter, you must specify at least first or/and second parameter.');
			}
			$this->serializationUnit = SerializationUnitFactory::getInstance($settings->getModel()->getName());
		} else {
			$rC = new \ReflectionClass($serializationUnitClass);
			if (!$rC->hasMethod('getInstance')) {
				throw new SerializationException(
					'invalid serialization unit class \'' . get_class($this->serializationUnit) . '\'. '
					. 'it must inherit from ' . SerializationUnit::class
				);
			}
			$this->serializationUnit = $serializationUnitClass::getInstance();
			if (!($this->serializationUnit instanceof SerializationUnit)) {
				throw new SerializationException(
					'invalid serialization unit class \'' . get_class($this->serializationUnit) . '\'. '
					. 'it must inherit from ' . SerializationUnit::class
				);
			}
			$this->serializationUnitClass = $serializationUnitClass;
		}
		$this->inheritanceValues = $inheritanceValues;
	}
	/**
	 *
	 * @param UniqueObject $settings
	 * @param string $inheritanceKey
	 * @param string[] $inheritanceValues
	 */
	public static function getInstanceWithSettings(UniqueObject $settings, $inheritanceKey = null, $inheritanceValues = null) {
		return new self($settings, null, $inheritanceKey, $inheritanceValues);
	}
	/**
	 *
	 * @param string $serializationUnitClass must be a class that inherit from SerializationUnit
	 * @param string $inheritanceKey
	 * @param string[] $inheritanceValues
	 */
	public static function getInstanceWithUnitClass($serializationUnitClass, $inheritanceKey = null, $inheritanceValues = null) {
		return new self(null, $serializationUnitClass, $inheritanceKey, $inheritanceValues);
	}
	
	/**
	 * get settings
	 *
	 * @return \Comhon\Object\UniqueObject
	 */
	public function getSettings() {
		return $this->settings;
	}
	
	/**
	 * get serialization unit class (only if specified during instanciation)
	 *
	 * @return string|null
	 */
	public function getSerializationUnitClass() {
		return $this->serializationUnitClass;
	}
	
	/**
	 * get inheritance key
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->inheritanceKey;
	}
	
	/**
	 * get inheritance key
	 *
	 * @return string
	 */
	public function getInheritanceValues() {
		return $this->inheritanceValues;
	}
	
	/**
	 * get serialization unit
	 *
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public function getSerializationUnit() {
		return $this->serializationUnit;
	}
	
}