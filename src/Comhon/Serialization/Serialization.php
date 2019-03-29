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
	
	/**
	 * 
	 * @param UniqueObject $settings
	 * @param string $inheritanceKey
	 */
	public function __construct(UniqueObject $settings, $inheritanceKey = null) {
		$this->settings = $settings;
		$this->inheritanceKey = $inheritanceKey;
		$this->serializationUnit = SerializationUnit::getInstance($settings->getModel()->getName());
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
	 * get serialization unit
	 *
	 * @return \Comhon\Serialization\SerializationUnit
	 */
	public function getSerializationUnit() {
		return $this->serializationUnit;
	}
	
}