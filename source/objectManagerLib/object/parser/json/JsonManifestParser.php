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
		
		$lManifestList = json_decode(file_get_contents($pManifestListPath_afe));
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
	
		$lSerializationList = json_decode(file_get_contents($pSerializationListPath_afe));
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
		$this->mManifest = simplexml_load_file($pManifestPath_afe);
	
		if ($this->mManifest === false || is_null($this->mManifest)) {
			throw new \Exception("manifest file not found '$pManifestPath_afe'");
		}
	}
	
	public function getExtends() {
		if ($this->mLocalTypeIndex == -1)  {
			return isset($this->mManifest[self::_EXTENDS]) ? (string) $this->mManifest[self::_EXTENDS] : null;
		} else {
			return isset($this->mManifest->types->type[$this->mLocalTypeIndex][self::_EXTENDS]) ? (string) $this->mManifest->types->type[$this->mLocalTypeIndex][self::_EXTENDS] : null;
		}
	}
	
	public function getObjectClass() {
		if ($this->mLocalTypeIndex == -1)  {
			return isset($this->mManifest[self::_OBJECT]) ? (string) $this->mManifest[self::_OBJECT] : null;
		} else {
			return isset($this->mManifest->types->type[$this->mLocalTypeIndex][self::_OBJECT]) ? (string) $this->mManifest->types->type[$this->mLocalTypeIndex][self::_OBJECT] : null;
		}
	}
	
	public function getCurrentLocalTypeId() {
		return isset($this->mManifest->types->type[$this->mLocalTypeIndex])
		? (string) $this->mManifest->types->type[$this->mLocalTypeIndex]['id']
		: null;
	}
	
	protected function _getLocalTypesCount() {
		return isset($this->mManifest->types->type)
					? count($this->mManifest->types->type)
					: 0;
	}
	
	protected function _getCurrentProperties() {
		return $this->mLocalTypeIndex == -1
				? $this->mManifest->properties->children()
				: $this->mManifest->types->type[$this->mLocalTypeIndex]->properties->children();
	}
	
	public function getCurrentPropertyModelName() {
		return isset($this->mCurrentProperties[$this->mCurrentPropertyIndex])
				? $this->_getPropertyModelName($this->mCurrentProperties[$this->mCurrentPropertyIndex])
				: null;
	}
	
	private function _getPropertyModelName($pProperty) {
		$lModelName = (string) $pProperty['type'];
		if ($lModelName == 'array') {
			$lModelName = $this->_getPropertyModelName($pProperty->values);
		}
		return $lModelName;
	}
	
	/**
	 * @return string
	 */
	protected function _getCurrentPropertyStatus() {
		return isset($this->mCurrentProperties[$this->mCurrentPropertyIndex])
				? $this->mCurrentProperties[$this->mCurrentPropertyIndex]->getName()
				: null;
	}
	
	protected function _getBaseInfosProperty(Model $pPropertyModel) {
		if (!isset($this->mCurrentProperties[$this->mCurrentPropertyIndex])) {
			throw new \Exception("current property index '$this->mCurrentPropertyIndex' doesn't exists");
		}
		$lCurrentPropertyXml = $this->mCurrentProperties[$this->mCurrentPropertyIndex];
		
		$lName   = isset($lCurrentPropertyXml->name) ? (string) $lCurrentPropertyXml->name : (string) $lCurrentPropertyXml;
		$lIsId   = (isset($lCurrentPropertyXml["id"]) && ((string) $lCurrentPropertyXml["id"] == "1")) ? true : false;
		$lModel  = $this->_completePropertyModel($lCurrentPropertyXml, $pPropertyModel);
		
		return array($lName, $lModel, $lIsId);
	}
	
	/**
	 * add model container if needed
	 * @param \SimpleXMLElement $pPropertyXml
	 * @param Model $pModel
	 * @throws \Exception
	 * @return Model
	 */
	private function _completePropertyModel($pPropertyXml, $pModel) {
		$lPropertyModel = null;
		$lTypeId        = (string) $pPropertyXml['type'];
	
		if ($lTypeId == 'array') {
			if (!isset($pPropertyXml->values['name'])) {
				throw new \Exception('type array must have a values name. property : '.(string) $pPropertyXml->name);
			}
			$lPropertyModel = new ModelArray($this->_completePropertyModel($pPropertyXml->values, $pModel), (string) $pPropertyXml->values['name']);
		}
		else {
			if ($pModel->getModelName() !== $lTypeId) {
				throw new \Exception('model doesn\'t match with type');
			}
			if (isset($pPropertyXml->enum)) {
				$lEnum = array();
				foreach ($pPropertyXml->enum->value as $lValue) {
					$lEnum[] = (string) $lValue;
				}
				$lPropertyModel = new ModelEnum($pModel, $lEnum);
			}else {
				$lPropertyModel = $pModel;
			}
		}
		return $lPropertyModel;
	}
	
	/**
	 * 
	 * @param array $pInstanceModels
	 * @throws Exception
	 */
	public function registerComplexLocalModels(&$pInstanceModels, $pManifestPath_ad) {
		if (isset($this->mManifest->manifests->manifest)) {
			foreach ($this->mManifest->manifests->manifest as $lManifest) {
				$lType = (string) $lManifest['type'];
				if (array_key_exists($lType, $pInstanceModels)) {
					throw new Exception("several model with same type : '$lType'");
				}
				$pManifestPath_rfe = (string) $lManifest;
				$pInstanceModels[$lType] = array($pManifestPath_ad.'/'.$pManifestPath_rfe, null);
			}
		}
	}
	
}