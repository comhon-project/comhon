<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser\V_2_0;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Manifest\Parser\SerializationManifestParser as ParentSerializationManifestParser;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Exception\Manifest\SerializationManifestIdException;

class SerializationManifestParser extends ParentSerializationManifestParser {
	
	/** @var string */
	const SERIALIZATION_NAME = 'serializationName';
	
	/** @var string */
	const SERIALIZATION_NAMES = 'serializationNames';
	
	/** @var string */
	const INHERITANCE_KEY = 'inheritanceKey';
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getPropertySerializationInfos()
	 */
	public function getPropertySerializationInfos($propertyName) {
		$serializationName  = null;
		$isSerializable     = true;
		$serializationNames = [];
		$properties         = $this->interfacer->getValue($this->manifest, 'properties', true);
		
		if (!is_null($properties) && $this->interfacer->hasValue($properties, $propertyName, true)) {
			$serializationNode = $this->interfacer->getValue($properties, $propertyName, true);
			if ($this->interfacer->hasValue($serializationNode, static::SERIALIZATION_NAME)) {
				$serializationName = $this->interfacer->getValue($serializationNode, static::SERIALIZATION_NAME);
				if ($this->interfacer->hasValue($serializationNode, static::SERIALIZATION_NAMES)) {
					throw new ManifestException('serializationName and serializationNames cannot cohexist');
				}
			}
			else if ($this->interfacer->hasValue($serializationNode, static::SERIALIZATION_NAMES, true)) {
				$serializationNames = $this->interfacer->getTraversableNode(
					$this->interfacer->getValue($serializationNode, static::SERIALIZATION_NAMES, true),
					true
				);
				if ($this->interfacer instanceof XMLInterfacer) {
					foreach ($serializationNames as $key => $serializationNameNode) {
						$serializationNames[$key] = $this->interfacer->extractNodeText($serializationNameNode);
					}
				}
			}
			if ($this->interfacer->hasValue($serializationNode, static::IS_SERIALIZABLE)) {
				$isSerializable = $this->interfacer->getValue($serializationNode, static::IS_SERIALIZABLE);
				if ($this->interfacer instanceof XMLInterfacer) {
					$isSerializable = $this->interfacer->castValueToBoolean($isSerializable);
				}
			}
		}
		
		return [$serializationName, $isSerializable, $serializationNames];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getAggregationInfos()
	 */
	public function getAggregationInfos($propertyName) {
		$aggregations = null;
		$properties = $this->interfacer->getValue($this->manifest, 'properties', true);
		
		if (!is_null($properties) && $this->interfacer->hasValue($properties, $propertyName, true)) {
			$propertyNode = $this->interfacer->getValue($properties, $propertyName, true);
			if ($this->interfacer->hasValue($propertyNode, static::AGGREGATIONS, true)) {
				$aggregations = $this->interfacer->getTraversableNode(
					$this->interfacer->getValue($propertyNode, static::AGGREGATIONS, true)
				);
				if ($this->interfacer instanceof XMLInterfacer) {
					foreach ($aggregations as $key => $serializationNameNode) {
						$aggregations[$key] = $this->interfacer->extractNodeText($serializationNameNode);
					}
				}
				if (empty($aggregations)) {
					throw new ManifestException('aggregation must have at least one aggregation property');
				}
			}
		}
		
		return $aggregations;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::shareParentSerialization()
	 */
	public function shareParentSerialization() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getSerializationSettings()
	 */
	public function getSerializationSettings() {
		return $this->interfacer->hasValue($this->manifest, static::SERIALIZATION, true)
			? $this->_buildSerializationSettings($this->interfacer->getValue($this->manifest, static::SERIALIZATION, true))
			: null;
	}
	
	/**
	 * build serialization settings
	 * 
	 * @param mixed $serializationNode
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _buildSerializationSettings($serializationNode) {
		$type = $this->interfacer->getValue($serializationNode, 'type');
		if ($this->interfacer->hasValue($serializationNode, 'value', true)) {
			$serializationSettings = ModelManager::getInstance()->getInstanceModel($type)->getObjectInstance();
			$serializationSettings->fill($this->interfacer->getValue($serializationNode, 'value', true), $this->interfacer);
		} elseif ($this->interfacer->hasValue($serializationNode, 'id')) {
			$id = $this->interfacer->getValue($serializationNode, 'id');
			if (empty($id)) {
				throw new ManifestException('malformed serialization, must have description or id');
			}
			$serializationSettings =  ModelManager::getInstance()->getInstanceModel($type)->loadObject($id);
			if (is_null($serializationSettings)) {
				throw new SerializationManifestIdException($type, $id);
			}
		} elseif (!$this->interfacer->hasValue($serializationNode, static::UNIT_CLASS)) {
			throw new ManifestException('malformed serialization');
		} else {
			$serializationSettings = null;
		}
		return $serializationSettings;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getSerializationUnitClass()
	 */
	public function getSerializationUnitClass() {
		$serializationNode = $this->interfacer->getValue($this->manifest, static::SERIALIZATION, true);
		return is_null($serializationNode)
			? null
			: (
				$this->interfacer->hasValue($serializationNode, static::UNIT_CLASS)
				? $this->interfacer->getValue($serializationNode, static::UNIT_CLASS)
				: null
			);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getInheritanceKey()
	 */
	public function getInheritanceKey() {
		$serializationNode = $this->interfacer->getValue($this->manifest, static::SERIALIZATION, true);
		return is_null($serializationNode)
			? null
			: (
				$this->interfacer->hasValue($serializationNode, static::INHERITANCE_KEY)
				? $this->interfacer->getValue($serializationNode, static::INHERITANCE_KEY)
				: null
			);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getInheritanceValues()
	 */
	public function getInheritanceValues() {
		$inheritanceValues = null;
		
		if ($this->interfacer->hasValue($this->manifest, static::INHERITANCE_VALUES, true)) {
			$node = $this->interfacer->getValue($this->manifest, static::INHERITANCE_VALUES, true);
			$inheritanceValues = $this->interfacer->getTraversableNode($node);
			if ($this->interfacer instanceof XMLInterfacer) {
				foreach ($inheritanceValues as $key => $domNode) {
					$inheritanceValues[$key] = $this->interfacer->extractNodeText($domNode);
				}
			}
		}
		
		return $inheritanceValues;
	}
	
}