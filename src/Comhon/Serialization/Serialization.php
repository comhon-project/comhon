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

final class Serialization {

	/** @var \Comhon\Object\UniqueObject */
	private $settings;
	
	/** @var string */
	private $inheritanceKey;
	
	/** @var \Comhon\Serialization\SerializationUnit */
	private $serializationUnit;
	
	/** @var string */
	private $allowSerialization;
	
	/** @var string[] */
	private $inheritanceValues;
	
	/**
	 * 
	 * @param UniqueObject $settings
	 * @param string $inheritanceKey
	 * @param bool $allowSerialization
	 * @param string[] $inheritanceValues
	 */
	public function __construct(UniqueObject $settings, $inheritanceKey = null, $allowSerialization = true, $inheritanceValues = null) {
		$this->settings = $settings;
		$this->inheritanceKey = $inheritanceKey;
		$this->serializationUnit = SerializationUnit::getInstance($settings->getModel()->getName());
		$this->allowSerialization = $allowSerialization;
		$this->inheritanceValues= $inheritanceValues;
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
	 * get inheritance key
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->inheritanceKey;
	}
	
	/**
	 * verify if serialization (save) is allowed.
	 * deserialization (load) still available even if serialization is not allowed.
	 *
	 * @return string
	 */
	public function isSerializationAllowed() {
		return $this->allowSerialization;
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