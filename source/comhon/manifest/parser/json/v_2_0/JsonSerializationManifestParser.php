<?php

namespace comhon\manifest\parser\json\v_2_0;

use comhon\model\singleton\ModelManager;
use comhon\manifest\parser\json\JsonSerializationManifestParser as ParentJsonSerializationManifestParser;

class JsonSerializationManifestParser extends ParentJsonSerializationManifestParser {

	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName  = null;
		$lAggregations       = null;
		$lIsSerializable     = true;
		$lSerializationNames = [];
		
		if (isset($this->mManifest->properties->$pPropertyName)) {
			$lSerializationNode = $this->mManifest->properties->$pPropertyName;
			if (isset($lSerializationNode->serializationName)) {
				$lSerializationName = $lSerializationNode->serializationName;
				if (isset($lSerializationNode->serializationNames)) {
					throw new \Exception('serializationName and serializationNames cannot cohexist');
				}
			}
			else if (isset($lSerializationNode->serializationNames) && is_object($lSerializationNode->serializationNames)) {
				$lSerializationNames = [];
				// transform stdclass to associative array
				foreach ($lSerializationNode->serializationNames as $lIdProperty => $lMultiSerializationName) {
					$lSerializationNames[$lIdProperty] = $lMultiSerializationName;
				}
			}
			if (isset($lSerializationNode->aggregations) && is_array($lSerializationNode->aggregations)) {
				$lAggregations = $lSerializationNode->aggregations;
			}
			if (isset($lSerializationNode->is_serializable)) {
				$lIsSerializable = $lSerializationNode->is_serializable;
			}
		}
		
		return [$lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames];
	}
	
	protected function _getSerializationSettings() {
		return isset($this->mManifest->serialization)
					? $this->_buildSerializationSettings($this->mManifest->serialization)
					: null;
	}
	
	private function _buildSerializationSettings($pSerializationNode) {
		$lType = $pSerializationNode->type;
		if (isset($pSerializationNode->value)) {
			$lSerialization = ModelManager::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromSerializedStdObject($pSerializationNode->value);
		} else if (isset($pSerializationNode->id)) {
			$lId = $pSerializationNode->id;
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
		return isset($this->mManifest->serialization)
			? isset($this->mManifest->serialization->inheritanceKey) ? $this->mManifest->serialization->inheritanceKey : null
			: null;
	}
	
}