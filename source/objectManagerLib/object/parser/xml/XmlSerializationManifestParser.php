<?php

namespace objectManagerLib\object\parser\xml;

use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\parser\SerializationManifestParser;
use objectManagerLib\object\singleton\InstanceModel;

class XmlSerializationManifestParser extends SerializationManifestParser {

	
	/**
	 * @param string $pManifestPath_afe
	 */
	protected function _loadManifest($pManifestPath_afe) {
		$this->mManifest = simplexml_load_file($pManifestPath_afe);
		
		if ($this->mManifest === false || is_null($this->mManifest)) {
			throw new \Exception("serialization manifest file not found '$pManifestPath_afe'");
		}
	}	

	
	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName = null;
		$lCompositions      = null;
		
		if (isset($this->mManifest->properties->$pPropertyName)) {
			$lSerializationNode = $this->mManifest->properties->$pPropertyName;
			if (isset($lSerializationNode['serializationName'])) {
				$lSerializationName = (string) $lSerializationNode['serializationName'];
			}
			if (isset($lSerializationNode->compositions->composition)) {
				$lCompositions = [];
				foreach ($lSerializationNode->compositions->composition as $lComposition) {
					$lCompositions[] = (string) $lComposition;
				}
			}
		}
		
		return array($lSerializationName, $lCompositions);
	}
	
	protected function _getSerialization() {
		return isset($this->mManifest->serialization)
					? $this->_buildSerialization($this->mManifest->serialization)
					: null;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lType = (string) $pSerializationNode["type"];
		if (isset($pSerializationNode->$lType)) {
			$lObjectXml = $pSerializationNode->$lType;
			$lSerialization = InstanceModel::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromXml($lObjectXml);
		} else {
			$lId = (string) $pSerializationNode;
			if (empty($lId)) {
				throw new \Exception('malformed serialization, must have description or id');
			}
			$lSerialization =  InstanceModel::getInstance()->getInstanceModel($lType)->loadObject($lId);
		}
		return $lSerialization;
	}
	
}