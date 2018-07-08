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
use Comhon\Exception\ManifestException;
use Comhon\Exception\SerializationManifestIdException;

class SerializationManifestParser extends ParentSerializationManifestParser {

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getPropertySerializationInfos()
	 */
	public function getPropertySerializationInfos($propertyName) {
		$serializationName  = null;
		$aggregations       = null;
		$isSerializable     = true;
		$serializationNames = [];
		$properties         = $this->interfacer->getValue($this->manifest, 'properties', true);
		
		if (!is_null($properties) && $this->interfacer->hasValue($properties, $propertyName, true)) {
			$serializationNode = $this->interfacer->getValue($properties, $propertyName, true);
			if ($this->interfacer->hasValue($serializationNode, 'serializationName')) {
				$serializationName = $this->interfacer->getValue($serializationNode, 'serializationName');
				if ($this->interfacer->hasValue($serializationNode, 'serializationNames')) {
					throw new ManifestException('serializationName and serializationNames cannot cohexist');
				}
			}
			else if ($this->interfacer->hasValue($serializationNode, 'serializationNames', true)) {
				$serializationNames = $this->interfacer->getTraversableNode(
					$this->interfacer->getValue($serializationNode, 'serializationNames', true),
					true
				);
				if ($this->interfacer instanceof XMLInterfacer) {
					foreach ($serializationNames as $key => $serializationNameNode) {
						$serializationNames[$key] = $this->interfacer->extractNodeText($serializationNameNode);
					}
				}
			}
			if ($this->interfacer->hasValue($serializationNode, 'aggregations', true)) {
				$aggregations = $this->interfacer->getTraversableNode(
					$this->interfacer->getValue($serializationNode, 'aggregations', true)
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
			if ($this->interfacer->hasValue($serializationNode, 'is_serializable')) {
				$isSerializable = $this->interfacer->getValue($serializationNode, 'is_serializable');
				if ($this->interfacer instanceof XMLInterfacer) {
					$isSerializable = $this->interfacer->castValueToBoolean($isSerializable);
				}
			}
		}
		
		return [$serializationName, $aggregations, $isSerializable, $serializationNames];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::_getSerializationSettings()
	 */
	protected function _getSerializationSettings() {
		return $this->interfacer->hasValue($this->manifest, 'serialization', true)
			? $this->_buildSerializationSettings($this->interfacer->getValue($this->manifest, 'serialization', true))
			: null;
	}
	
	/**
	 * build serialization settings
	 * 
	 * @param mixed $serializationNode
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique
	 */
	private function _buildSerializationSettings($serializationNode) {
		$type = $this->interfacer->getValue($serializationNode, 'type');
		if ($this->interfacer->hasValue($serializationNode, 'value', true)) {
			$serialization = ModelManager::getInstance()->getInstanceModel($type)->getObjectInstance();
			$serialization->fill($this->interfacer->getValue($serializationNode, 'value', true), $this->interfacer);
		} else if ($this->interfacer->hasValue($serializationNode, 'id')) {
			$id = $this->interfacer->getValue($serializationNode, 'id');
			if (empty($id)) {
				throw new ManifestException('malformed serialization, must have description or id');
			}
			$serialization =  ModelManager::getInstance()->getInstanceModel($type)->loadObject($id);
			if (is_null($serialization)) {
				throw new SerializationManifestIdException($type, $id);
			}
		} else {
			throw new ManifestException('malformed serialization');
		}
		return $serialization;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\SerializationManifestParser::getInheritanceKey()
	 */
	public function getInheritanceKey() {
		$serializationNode = $this->interfacer->getValue($this->manifest, 'serialization', true);
		return is_null($serializationNode)
			? null
			: (
				$this->interfacer->hasValue($serializationNode, 'inheritanceKey')
					? $this->interfacer->getValue($serializationNode, 'inheritanceKey')
					: null
			);
	}
	
}