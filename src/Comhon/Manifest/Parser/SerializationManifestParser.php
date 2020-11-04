<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser;

use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Exception\ArgumentException;

abstract class SerializationManifestParser {
	
	/** @var string */
	const SERIALIZATION = 'serialization';
	
	/** @var string */
	const INHERITANCE_VALUES = 'inheritance_values';
	
	/** @var string */
	const SERIALIZATION_NAME = 'serialization_name';
	
	/** @var string */
	const SERIALIZATION_NAMES = 'serialization_names';
	
	/** @var string */
	const INHERITANCE_KEY = 'inheritance_key';
	
	/** @var string */
	const AGGREGATIONS = 'aggregations';
	
	/** @var string */
	const IS_SERIALIZABLE = 'is_serializable';
	
	const UNIT_CLASS = 'serialization_unit_class';
	
	/** @var mixed */
	protected $manifest;
	
	/** @var \Comhon\Interfacer\Interfacer */
	protected $interfacer;
	
	/** @var boolean */
	protected $castValues;
	
	/** @var mixed[] */
	private $indexedProperties;

	/**
	 * get serialization informations of property
	 * 
	 * @param string $propertyName
	 * @return array
	 */
	abstract public function getPropertySerializationInfos($propertyName);
	
	/**
	 * get aggregation properties (only for serialization manifest 2.0)
	 *
	 * @param string $propertyName
	 * @return string[]|null
	 */
	abstract public function getAggregationInfos($propertyName);
	
	/**
	 * verify if serialization of parent model must be shared with current model
	 *
	 * @return \Comhon\Object\UniqueObject
	 */
	abstract public function shareParentSerialization();
	
	/**
	 * get serialization settings
	 *
	 * @return \Comhon\Object\UniqueObject
	 */
	abstract public function getSerializationSettings();
	
	/**
	 * get serialization unit class
	 *
	 * @return string|null
	 */
	abstract public function getSerializationUnitClass();
	
	/**
	 * get inheritance key
	 * 
	 * @return string
	 */
	abstract public function getInheritanceKey();
	
	/**
	 * get inherited model values that have to be specified in deserialization query
	 *
	 * @return string[]|null null if no inherited model values
	 */
	abstract public function getInheritanceValues();
	
	/**
	 * get properties nodes indexed by their names
	 *
	 * @return array
	 */
	abstract protected function _getIndexedProperties();
	
	/**
	 * @param mixed $manifest
	 */
	final public function __construct($manifest) {
		$this->interfacer = $this->_getInterfacer($manifest);
		$this->manifest   = $manifest;
		
		$this->interfacer->setSerialContext(true);
		$this->interfacer->setPrivateContext(true);
		$this->castValues = ($this->interfacer instanceof NoScalarTypedInterfacer);
	}
	
	/**
	 * get property node according given name
	 * 
	 * @param string $propertyName
	 * @return NULL|mixed
	 */
	protected function _getPropertyNode($propertyName) {
		if (is_null($this->indexedProperties)) {
			$this->indexedProperties = $this->_getIndexedProperties();
		}
		return array_key_exists($propertyName, $this->indexedProperties) ? $this->indexedProperties[$propertyName] : null;
	}
	
	/**
	 * get boolean value from serialization manifest (cast if necessary)
	 *
	 * @param mixed $node node
	 * @param string $name value's name
	 * @param boolean $defaultValue used if value not found
	 * @return boolean
	 */
	protected function _getBooleanValue($node, $name, $defaultValue) {
		return $this->interfacer->hasValue($node, $name)
		? (
				$this->castValues
				? $this->interfacer->castValueToBoolean($this->interfacer->getValue($node, $name))
				: $this->interfacer->getValue($node, $name)
				)
				: $defaultValue;
	}
	
	/**
	 * get serialization manifest parser instance
	 * 
	 * @param string $serializationManifestPath_afe
	 * @throws \Exception
	 * @return \Comhon\Manifest\Parser\SerializationManifestParser
	 */
	public static function getInstance($serializationManifestPath_afe) {
		try {
			$interfacer = Interfacer::getInstance(
				mb_strtolower(pathinfo($serializationManifestPath_afe, PATHINFO_EXTENSION)),
				true
			);
		} catch (ArgumentException $e) {
			throw new ManifestException('extension not recognized for manifest file : '.$serializationManifestPath_afe);
		}
		return self::_getInstanceWithInterfacer($serializationManifestPath_afe, $interfacer);
		
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * 
	 * @param mixed $manifest
	 * @return \Comhon\Interfacer\Interfacer
	 */
	public function _getInterfacer($manifest) {
		if (is_array($manifest)) {
			$interfacer = new AssocArrayInterfacer();
		}
		elseif ($manifest instanceof \stdClass) {
			$interfacer =  new StdObjectInterfacer();
		}
		elseif ($manifest instanceof \DOMElement) {
			$interfacer =  new XMLInterfacer();
		} else {
			throw new ManifestException('not recognized manifest format');
		}
		$interfacer->setMergeType(Interfacer::OVERWRITE);
		$interfacer->setVerifyReferences(false);
		return $interfacer;
	}
	
	/**
	 * get manifest parser instance
	 *
	 * @param string $serializationManifestPath_afe
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return SerializationManifestParser
	 */
	private static function _getInstanceWithInterfacer($serializationManifestPath_afe, Interfacer $interfacer) {
		$manifest = $interfacer->read($serializationManifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new ManifestException("serialization manifest file not found or malformed '$serializationManifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new ManifestException("serialization manifest '$serializationManifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\SerializationManifestParser($manifest);
			case '3.0': return new V_3_0\SerializationManifestParser($manifest);
			default:    throw new ManifestException("version $version not recognized for manifest $serializationManifestPath_afe");
		}
	}
}