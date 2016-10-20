<?php

namespace objectManagerLib\object\parser\json;

use \Exception;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\ModelEnum;
use objectManagerLib\object\model\Integer;
use objectManagerLib\object\model\Float;
use objectManagerLib\object\model\Boolean;
use objectManagerLib\object\model\String;
use objectManagerLib\object\model\DateTime;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\LocalModel;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\SerializationUnit;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\CompositionProperty;
use objectManagerLib\object\object\Config;
use objectManagerLib\object\parser\ManifestParser;
use objectManagerLib\object\parser\SerializationManifestParser;

class JsonManifestParser extends ManifestParser {

	
	/**
	 * register path of each manifest
	 * @param string $pManifestListPath_afe
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 * @throws \Exception
	 */
	protected static function _registerComplexModels($pManifestListPath_afe, $pSerializationMap, &$pModelMap) {
		$lManifestListFolder_ad = dirname($pManifestListPath_afe);
		
		$lManifestList = json_decode(file_get_contents($pManifestListPath_afe), true);
		if ($lManifestList === false || is_null($lManifestList)) {
			throw new \Exception("manifestList file not found or malformed '$pManifestListPath_afe'");
		}
		foreach ($lManifestList as $lModelName => $lManifestPath_rfe) {
			if (array_key_exists($lModelName, $pModelMap)) {
				throw new Exception("several model with same type : '$lModelName'");
			}
			$lSerializationPath_afe = array_key_exists($lModelName, $pSerializationMap) ? $pSerializationMap[$lModelName] : null;
			$pModelMap[$lModelName] = array($lManifestListFolder_ad.'/'.$lManifestPath_rfe, $lSerializationPath_afe);
		}
	}
	
	/**
	 * 
	 * @param string $pSerializationListPath_afe
	 * @throws \Exception
	 * @return string[]
	 */
	protected static function _getSerializationMap($pSerializationListPath_afe) {
		$lSerializationMap = [];
		$pSerializationListFolrder_ad = dirname($pSerializationListPath_afe);
	
		$lSerializationList = json_decode(file_get_contents($pSerializationListPath_afe), true);
		if ($lSerializationList === false || is_null($lSerializationList)) {
			throw new \Exception("serializationList file not found or malformed '$pSerializationListPath_afe'");
		}
		foreach ($lSerializationList as $lModelName => $lSerializationPath_rfe) {
			$lSerializationMap[$lModelName] = $pSerializationListFolrder_ad.'/'.$lSerializationPath_rfe;
		}
	
		return $lSerializationMap;
	}
	
	/**
	 * @param string $pManifestPath_afe
	 */
	public function _loadManifest($pManifestPath_afe) {
		$this->mManifest = json_decode(file_get_contents($pManifestPath_afe), true);
	
		if ($this->mManifest === false || is_null($this->mManifest)) {
			throw new \Exception("manifest file not found or malformed '$pManifestPath_afe'");
		}
	}
	
	public function getExtends() {
		if ($this->mFocusLocalTypes)  {
			return isset(current($this->mLocalTypes)[self::_EXTENDS]) ? current($this->mLocalTypes)[self::_EXTENDS] : null;
		} else {
			return isset($this->mManifest[self::_EXTENDS]) ? $this->mManifest[self::_EXTENDS] : null;
		}
	}
	
	public function getObjectClass() {
		if ($this->mFocusLocalTypes)  {
			return isset(current($this->mLocalTypes)[self::_OBJECT]) ? current($this->mLocalTypes)[self::_OBJECT] : null;
		} else {
			return isset($this->mManifest[self::_OBJECT]) ? $this->mManifest[self::_OBJECT] : null;
		}
	}
	
	public function getCurrentLocalTypeId() {
		return key($this->mLocalTypes);
	}
	
	protected function _getLocalTypes() {
		return array_key_exists('types', $this->mManifest) ? $this->mManifest['types'] : [];
	}
	
	protected function _getCurrentProperties() {
		return $this->mFocusLocalTypes ? current($this->mLocalTypes)['properties'] : $this->mManifest['properties'];
	}
	
	public function getCurrentPropertyModelName() {
		return $this->_getPropertyModelName(current($this->mCurrentProperties));
	}
	
	private function _getPropertyModelName($pProperty) {
		$lModelName = $pProperty['type'];
		if ($lModelName == 'array') {
			$lModelName = $this->_getPropertyModelName($pProperty['values']);
		}
		return $lModelName;
	}
	
	/**
	 * @return string
	 */
	protected function _isCurrentPropertyForeign() {
		$lCurrentProperty = current($this->mCurrentProperties);
		return isset($lCurrentProperty['is_foreign']) && $lCurrentProperty['is_foreign'];
	}
	
	protected function _getBaseInfosProperty(Model $pPropertyModel) {
		$lCurrentPropertyJson = current($this->mCurrentProperties);
	
		$lName   = key($this->mCurrentProperties);
		$lIsId   = array_key_exists('is_id', $lCurrentPropertyJson) && $lCurrentPropertyJson['is_id'];
		$lModel  = $this->_completePropertyModel($lCurrentPropertyJson, $pPropertyModel);
	
		return array($lName, $lModel, $lIsId);
	}
	
	/**
	 * add model container if needed
	 * @param \SimpleJsonElement $pPropertyJson
	 * @param Model $pModel
	 * @throws \Exception
	 * @return Model
	 */
	private function _completePropertyModel($pPropertyJson, $pModel) {
		$lPropertyModel = null;
		$lTypeId        = $pPropertyJson['type'];
	
		if ($lTypeId == 'array') {
			if (!isset($pPropertyJson['values']['name'])) {
				throw new \Exception('type array must have a values name. property');
			}
			$lPropertyModel = new ModelArray($this->_completePropertyModel($pPropertyJson['values'], $pModel), $pPropertyJson['values']['name']);
		}
		else {
			if ($pModel->getModelName() !== $lTypeId) {
				throw new \Exception('model doesn\'t match with type');
			}
			$lPropertyModel = isset($pPropertyJson['enum']) ? new ModelEnum($pModel, $pPropertyJson['enum']) : $pModel;
		}
		return $lPropertyModel;
	}
	
	/**
	 *
	 * @param array $pInstanceModels
	 * @throws Exception
	 */
	public function registerComplexLocalModels(&$pInstanceModels, $pManifestPath_ad) {
		if (isset($this->mManifest['manifests'])) {
			foreach ($this->mManifest['manifests'] as $lType => $lManifestPath_rfe) {
				if (array_key_exists($lType, $pInstanceModels)) {
					throw new Exception("several model with same type : '$lType'");
				}
				$pInstanceModels[$lType] = array($pManifestPath_ad.'/'.$lManifestPath_rfe, null);
			}
		}
	}
	
}