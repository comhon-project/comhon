<?php

namespace objectManagerLib\object\parser\json;

use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\parser\SerializationManifestParser;
use objectManagerLib\object\singleton\InstanceModel;

class JsonSerializationManifestParser extends SerializationManifestParser {

	
	/**
	 * @param string $pManifestPath_afe
	 */
	protected function _loadManifest($pManifestPath_afe) {
		$this->mManifest = json_decode(file_get_contents($pManifestPath_afe), true);
		
		if ($this->mManifest === false || is_null($this->mManifest)) {
			throw new \Exception("serialization manifest file not found '$pManifestPath_afe'");
		}
	}	

	
	public function getPropertySerializationInfos($pPropertyName) {
		$lSerializationName = null;
		$lCompositions      = null;
		
		if (isset($this->mManifest['properties'][$pPropertyName])) {
			$lSerializationNode = $this->mManifest['properties'][$pPropertyName];
			if (isset($lSerializationNode['serializationName'])) {
				$lSerializationName = $lSerializationNode['serializationName'];
			}
			if (isset($lSerializationNode['compositions'])) {
				$lCompositions = [];
				foreach ($lSerializationNode['compositions'] as $lComposition) {
					$lCompositions[] = $lComposition;
				}
			}
		}
		
		return array($lSerializationName, $lCompositions);
	}
	
	protected function _getSerialization() {
		return isset($this->mManifest['serialization'])
					? $this->_buildSerialization($this->mManifest['serialization'])
					: null;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lType = $pSerializationNode['type'];
		if (isset($pSerializationNode['value'])) {
			$lSerialization = InstanceModel::getInstance()->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromObject($pSerializationNode['value']);
		} else if (isset($pSerializationNode['id'])) {
			$lId = $pSerializationNode['id'];
			if (empty($lId)) {
				throw new \Exception('malformed serialization, must have description or id');
			}
			$lSerialization =  InstanceModel::getInstance()->getInstanceModel($lType)->loadObject($lId);
		} else {
			throw new \Exception('malformed serialization');
		}
		return $lSerialization;
	}
	
}