<?php

namespace comhon\manifest\parser\xml\v_2_0;

use \Exception;
use comhon\model\ModelArray;
use comhon\model\ModelEnum;
use comhon\model\ModelDateTime;
use comhon\model\Model;
use comhon\model\property\Property;
use comhon\model\SimpleModel;
use comhon\model\property\ForeignProperty;
use comhon\manifest\parser\xml\XmlManifestParser as ParentXmlManifestParser;
use comhon\interfacer\XMLInterfacer;

class XmlManifestParser extends ParentXmlManifestParser {
	
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
		return current($this->mLocalTypes)->getName();
	}
	
	protected function _getLocalTypes() {
		$lArrayLocaltTypes = [];
		if (isset($this->mManifest->types)) {
			foreach ($this->mManifest->types->children() as $lXmlLocalType) {
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
		
		$lName      = isset($lCurrentPropertyXml->name) ? (string) $lCurrentPropertyXml->name : (string) $lCurrentPropertyXml;
		$lIsId      = isset($lCurrentPropertyXml['id']) && ((string) $lCurrentPropertyXml['id'] == '1');
		$lIsPrivate = isset($lCurrentPropertyXml['private']) && ((string) $lCurrentPropertyXml['private'] == '1');
		$lModel     = $this->_completePropertyModel($lCurrentPropertyXml, $pPropertyModel);
		
		if (isset($lCurrentPropertyXml['xml'])) {
			$lType = (string) $lCurrentPropertyXml['xml'];
			if ($lType === self::XML_ATTRIBUTE) {
				$lInterfaceAsNodeXml = false;
			} else if ($lType === self::XML_NODE) {
				$lInterfaceAsNodeXml = true;
			} else {
				throw new \Exception('invalid xml value : '.$lType);
			}
		} else {
			$lInterfaceAsNodeXml = null;
		}
		
		if (isset($lCurrentPropertyXml['default'])) {
			if ($lModel instanceof ModelDateTime) {
				$lDefault = (string) $lCurrentPropertyXml['default'];
				if (new \DateTime($lDefault) === false) {
					throw new \Exception('invalid default value time format : '.$lDefault);
				}
			} else if ($lModel instanceof SimpleModel) {
				$lXMLInterfacer = new XMLInterfacer();
				$lDefault = $lModel->importSimple($lCurrentPropertyXml['default'], $lXMLInterfacer);
			} else if ($lModel instanceof ModelEnum) {
				$lXMLInterfacer = new XMLInterfacer();
				$lDefault = $lModel->getModel()->importSimple($lCurrentPropertyXml['default'], $lXMLInterfacer);
			} else {
				throw new \Exception('default value can\'t be applied on complex model');
			}
		} else {
			$lDefault = null;
		}
		
		return [$lName, $lModel, $lIsId, $lIsPrivate, $lDefault, $lInterfaceAsNodeXml];
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
			if ($pModel->getName() !== $lTypeId) {
				throw new \Exception('model doesn\'t match with type');
			}
			if (isset($pPropertyXml->enum)) {
				$lEnum = [];
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
		if (isset($this->mManifest->manifests)) {
			foreach ($this->mManifest->manifests->children() as $lManifest) {
				$lModelName = $lManifest->getName();
				if (array_key_exists($lModelName, $pInstanceModels)) {
					throw new Exception("several model with same type : '$lModelName'");
				}
				$pManifestPath_rfe = (string) $lManifest;
				$pInstanceModels[$lModelName] = [$pManifestPath_ad.'/'.$pManifestPath_rfe, null];
			}
		}
	}
	
}