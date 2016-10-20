<?php

namespace objectManagerLib\object\parser\xml;

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

class XmlManifestParser extends ManifestParser {

	
	/**
	 * register path of each manifest
	 * @param string $pManifestListPath_afe
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 * @throws \Exception
	 */
	protected static function _registerComplexModels($pManifestListPath_afe, $pSerializationMap, &$pModelMap) {
		$lManifestListFolder_ad = dirname($pManifestListPath_afe);
		
		$lManifestList = simplexml_load_file($pManifestListPath_afe);
		if ($lManifestList === false || is_null($lManifestList)) {
			throw new \Exception("manifestList file not found or malformed '$pManifestListPath_afe'");
		}
		foreach ($lManifestList->manifest as $lManifest) {
			$lType = (string) $lManifest['type'];
			if (array_key_exists($lType, $pModelMap)) {
				throw new Exception("several model with same type : '$lType'");
			}
			$lManifestPath_rfe = (string) $lManifest;
			$lSerializationPath_afe = array_key_exists($lType, $pSerializationMap) ? $pSerializationMap[$lType] : null;
			$pModelMap[$lType] = array($lManifestListFolder_ad.'/'.$lManifestPath_rfe, $lSerializationPath_afe);
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
	
		$lSerializationList = simplexml_load_file($pSerializationListPath_afe);
		if ($lSerializationList === false || is_null($lSerializationList)) {
			throw new \Exception("serializationList file not found or malformed '$pSerializationListPath_afe'");
		}
		foreach ($lSerializationList->serialization as $lSerialization) {
			$lSerializationPath_rfe = (string) $lSerialization;
			$lSerializationMap[(string) $lSerialization['type']] = $pSerializationListFolrder_ad.'/'.$lSerializationPath_rfe;
		}
	
		return $lSerializationMap;
	}
	
	/**
	 * @param string $pManifestPath_afe
	 */
	public function _loadManifest($pManifestPath_afe) {
		$this->mManifest = simplexml_load_file($pManifestPath_afe);
	
		if ($this->mManifest === false || is_null($this->mManifest)) {
			throw new \Exception("manifest file not found or malformed '$pManifestPath_afe'");
		}
	}
	
	public function getExtends() {
		if ($this->mFocusLocalTypes)  {
			return isset(current($this->mLocalTypes)[self::_EXTENDS]) ? (string) current($this->mLocalTypes)[self::_EXTENDS] : null;
		} else {
			return isset($this->mManifest[self::_EXTENDS]) ? (string) $this->mManifest[self::_EXTENDS] : null;
		}
	}
	
	public function getObjectClass() {
		if ($this->mFocusLocalTypes)  {
			return isset(current($this->mLocalTypes)[self::_OBJECT]) ? (string) current($this->mLocalTypes)[self::_OBJECT] : null;
		} else {
			return isset($this->mManifest[self::_OBJECT]) ? (string) $this->mManifest[self::_OBJECT] : null;
		}
	}
	
	public function getCurrentLocalTypeId() {
		return (string) current($this->mLocalTypes)['id'];
	}
	
	protected function _getLocalTypes() {
		$lArrayLocaltTypes = [];
		if (isset($this->mManifest->types->type)) {
			foreach ($this->mManifest->types->type as $lXmlLocalType) {
				$lArrayLocaltTypes[] = $lXmlLocalType;
			}
		}
		return $lArrayLocaltTypes;
	}
	
	protected function _getCurrentProperties() {
		$lArrayProperties = [];
		$lXmlProperties   = $this->mFocusLocalTypes ? current($this->mLocalTypes)->properties : $this->mManifest->properties;
		
		foreach ($lXmlProperties->children() as $lXmlProperty) {
			$lArrayProperties[] = $lXmlProperty;
		}
		return $lArrayProperties;
	}
	
	public function getCurrentPropertyModelName() {
		return $this->_getPropertyModelName(current($this->mCurrentProperties));
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
	protected function _isCurrentPropertyForeign() {
		if (current($this->mCurrentProperties)->getName() == 'property') {
			return false;
		} else if (current($this->mCurrentProperties)->getName() == 'foreignProperty') {
			return true;
		}
		throw new \Exception('property node name not recognized');
	}
	
	protected function _getBaseInfosProperty(Model $pPropertyModel) {
		$lCurrentPropertyXml = current($this->mCurrentProperties);
		
		$lName   = isset($lCurrentPropertyXml->name) ? (string) $lCurrentPropertyXml->name : (string) $lCurrentPropertyXml;
		$lIsId   = isset($lCurrentPropertyXml["id"]) && ((string) $lCurrentPropertyXml["id"] == "1");
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