<?php

namespace comhon\manifest\parser\v_2_0;

use comhon\model\singleton\ModelManager;
use comhon\manifest\parser\SerializationManifestParser as ParentSerializationManifestParser;
use comhon\interfacer\XMLInterfacer;

class SerializationManifestParser extends ParentSerializationManifestParser {

	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName  = null;
		$lAggregations       = null;
		$lIsSerializable     = true;
		$lSerializationNames = [];
		$lProperties         = $this->mInterfacer->getValue($this->mManifest, 'properties', true);
		
		if (!is_null($lProperties) && $this->mInterfacer->hasValue($lProperties, $pPropertyName, true)) {
			$lSerializationNode = $this->mInterfacer->getValue($lProperties, $pPropertyName, true);
			if ($this->mInterfacer->hasValue($lSerializationNode, 'serializationName')) {
				$lSerializationName = $this->mInterfacer->getValue($lSerializationNode, 'serializationName');
				if ($this->mInterfacer->hasValue($lSerializationNode, 'serializationNames')) {
					throw new \Exception('serializationName and serializationNames cannot cohexist');
				}
			}
			else if ($this->mInterfacer->hasValue($lSerializationNode, 'serializationNames', true)) {
				$lSerializationNames = $this->mInterfacer->getTraversableNode(
					$this->mInterfacer->getValue($lSerializationNode, 'serializationNames', true),
					true
				);
				if ($this->mInterfacer instanceof XMLInterfacer) {
					foreach ($lSerializationNames as $lKey => $lSerializationNameNode) {
						$lSerializationNames[$lKey] = $this->mInterfacer->extractNodeText($lSerializationNameNode);
					}
				}
			}
			if ($this->mInterfacer->hasValue($lSerializationNode, 'aggregations', true)) {
				$lAggregations = $this->mInterfacer->getTraversableNode(
					$this->mInterfacer->getValue($lSerializationNode, 'aggregations', true)
				);
				if ($this->mInterfacer instanceof XMLInterfacer) {
					foreach ($lAggregations as $lKey => $lSerializationNameNode) {
						$lAggregations[$lKey] = $this->mInterfacer->extractNodeText($lSerializationNameNode);
					}
				}
			}
			if ($this->mInterfacer->hasValue($lSerializationNode, 'is_serializable')) {
				$lIsSerializable = $this->mInterfacer->getValue($lSerializationNode, 'is_serializable');
				if ($this->mInterfacer instanceof XMLInterfacer) {
					$lIsSerializable = $this->mInterfacer->castValueToBoolean($lIsSerializable);
				}
			}
		}
		
		return [$lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames];
	}
	
	protected function _getSerializationSettings() {
		return $this->mInterfacer->hasValue($this->mManifest, 'serialization', true)
			? $this->_buildSerializationSettings($this->mInterfacer->getValue($this->mManifest, 'serialization', true))
			: null;
	}
	
	private function _buildSerializationSettings($pSerializationNode) {
		$lType = $this->mInterfacer->getValue($pSerializationNode, 'type');
		if ($this->mInterfacer->hasValue($pSerializationNode, 'value', true)) {
			$lSerialization = ModelManager::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fill($this->mInterfacer->getValue($pSerializationNode, 'value', true), $this->mInterfacer);
		} else if ($this->mInterfacer->hasValue($pSerializationNode, 'id')) {
			$lId = $this->mInterfacer->getValue($pSerializationNode, 'id');
			if (empty($lId)) {
				throw new \Exception('malformed serialization, must have description or id');
			}
			$lSerialization =  ModelManager::getInstance()->getInstanceModel($lType)->loadObject($lId);
			if (is_null($lSerialization)) {
				throw new \Exception("impossible to load $lType serialization with id '$lId'");
			}
		} else {
			throw new \Exception('malformed serialization');
		}
		return $lSerialization;
	}
	
	public function getInheritanceKey() {
		$lSerializationNode = $this->mInterfacer->getValue($this->mManifest, 'serialization', true);
		return is_null($lSerializationNode)
			? null
			: (
				$this->mInterfacer->hasValue($lSerializationNode, 'inheritanceKey')
					? $this->mInterfacer->getValue($lSerializationNode, 'inheritanceKey')
					: null
			);
	}
	
}