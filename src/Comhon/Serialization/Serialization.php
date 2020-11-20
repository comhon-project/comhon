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
use Comhon\Model\Model;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\StdObjectInterfacer;

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
	
	/** @var string */
	private $settingsParentName = null;
	
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
	
	/**
	 * serialize model.
	 * this function must be called only in caching context.
	 *
	 * @param \Comhon\Model\Model $parent
	 */
	public function serialize(Model $parent = null) {
		if (!ModelManager::getInstance()->isCachingContext()) {
			throw new ComhonException('error function serialize may be called only in caching context');
		}
		if (!is_null($this->settings)) {
			if (!is_null($parent)) {
				if ($parent->getSerializationSettings() !== $this->getSettings()) {
					throw new ComhonException('not same serialization settings');
				}
				$this->settingsParentName = $parent->getName();
				$this->settings = null;
			} else {
				$interfacer = new StdObjectInterfacer();
				$interfacer->setPrivateContext(true);
				$interfacer->setVerifyReferences(false);
				$interfacer->setValidate(false);
				// get parent model to have inheritance key
				$this->settings = $this->settings->getModel()->getParent()->export($this->settings, $interfacer);
			}
		}
		$this->serializationUnit = get_class($this->serializationUnit);
	}
	
	/**
	 * restore model that have been unserialized from cache.
	 * this function must be called only in caching context.
	 */
	public function restore() {
		$modelManager = ModelManager::getInstance();
		if (!$modelManager->isCachingContext()) {
			throw new ComhonException('error function restore may be called only in caching context');
		}
		if (!is_null($this->settingsParentName)) {
			$this->settings = $modelManager->getInstanceModel($this->settingsParentName)->getSerializationSettings();
		} elseif (!is_null($this->settings)) {
			$interfacer = new StdObjectInterfacer();
			$interfacer->setPrivateContext(true);
			$interfacer->setVerifyReferences(false);
			$interfacer->setValidate(false);
			$this->settings = $modelManager->getInstanceModel('Comhon\Root')->import($this->settings, $interfacer);
		}
		$serializationUnitClass = $this->serializationUnit;
		$this->serializationUnit = $serializationUnitClass::getInstance();
	}
	
}