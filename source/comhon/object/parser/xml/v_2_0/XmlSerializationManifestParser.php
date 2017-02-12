<?php

namespace comhon\object\parser\xml\v_2_0;

use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\parser\SerializationManifestParser;
use comhon\object\singleton\ModelManager;
use comhon\object\parser\xml\XmlSerializationManifestParser as ParentXmlSerializationManifestParser;

class XmlSerializationManifestParser extends ParentXmlSerializationManifestParser {
	
	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName  = null;
		$lAggregations       = null;
		$lIsSerializable     = true;
		$lSerializationNames = [];
		
		if (isset($this->mManifest->properties->$pPropertyName)) {
			$lSerializationNode = $this->mManifest->properties->$pPropertyName;
			if (isset($lSerializationNode['serializationName'])) {
				$lSerializationName = (string) $lSerializationNode['serializationName'];
				if (isset($lSerializationNode['serializationNames'])) {
					throw new \Exception('serializationName and serializationNames cannot cohexist');
				}
			}
			else if (isset($lSerializationNode->serializationNames)) {
				$lSerializationNames = [];
				foreach ($lSerializationNode->serializationNames->children() as $lMultiSerializationName) {
					$lSerializationNames[$lMultiSerializationName->getName()] = (string) $lMultiSerializationName;
				}
			}
			if (isset($lSerializationNode->aggregations->aggregation)) {
				$lAggregations = [];
				foreach ($lSerializationNode->aggregations->aggregation as $lAggregation) {
					$lAggregations[] = (string) $lAggregation;
				}
			}
			if (isset($lSerializationNode['serializable'])) {
				$lIsSerializable = (string) $lSerializationNode['serializable'] !== '0';
			}
		}
		
		return [$lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames];
	}
	
	protected function _getSerialization() {
		return isset($this->mManifest->serialization)
					? $this->_buildSerialization($this->mManifest->serialization)
					: null;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lType = (string) $pSerializationNode['type'];
		if (isset($pSerializationNode->$lType)) {
			$lObjectXml = $pSerializationNode->$lType;
			$lSerialization = ModelManager::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromXml($lObjectXml, true, true);
		} else {
			$lId = (string) $pSerializationNode;
			if (empty($lId)) {
				throw new \Exception('malformed serialization, must have description or id');
			}
			$lSerialization =  ModelManager::getInstance()->getInstanceModel($lType)->loadObject($lId);
			if (is_null($lSerialization)) {
				throw new \Exception("impossible to load $lType serialization with id '$lId'");
			}
		}
		if (isset($pSerializationNode['inheritanceKey'])) {
			$lSerialization->setInheritanceKey((string) $pSerializationNode['inheritanceKey']);
		}
		return $lSerialization;
	}
	
}