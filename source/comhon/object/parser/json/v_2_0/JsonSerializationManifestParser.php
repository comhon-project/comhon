<?php

namespace comhon\object\parser\json\v_2_0;

use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\parser\SerializationManifestParser;
use comhon\object\singleton\ModelManager;
use comhon\object\parser\json\JsonSerializationManifestParser as ParentJsonSerializationManifestParser;

class JsonSerializationManifestParser extends ParentJsonSerializationManifestParser {

	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName = null;
		$lAggregations      = null;
		$lIsSerializable    = true;
		
		if (isset($this->mManifest->properties->$pPropertyName)) {
			$lSerializationNode = $this->mManifest->properties->$pPropertyName;
			if (isset($lSerializationNode->serializationName)) {
				$lSerializationName = $lSerializationNode->serializationName;
			}
			if (isset($lSerializationNode->aggregations)) {
				$lAggregations = [];
				foreach ($lSerializationNode->aggregations as $lAggregation) {
					$lAggregations[] = $lAggregation;
				}
			}
			if (isset($lSerializationNode->is_serializable)) {
				$lIsSerializable = $lSerializationNode->is_serializable;
			}
		}
		
		return array($lSerializationName, $lAggregations, $lIsSerializable);
	}
	
	protected function _getSerialization() {
		return isset($this->mManifest->serialization)
					? $this->_buildSerialization($this->mManifest->serialization)
					: null;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lType = $pSerializationNode->type;
		if (isset($pSerializationNode->value)) {
			$lSerialization = ModelManager::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromStdObject($pSerializationNode->value, true, true);
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
		if (isset($pSerializationNode->inheritanceKey)) {
			$lSerialization->setInheritanceKey($pSerializationNode->inheritanceKey);
		}
		return $lSerialization;
	}
	
}