<?php

namespace comhon\manifest\parser\json\v_2_0;

use \Exception;
use comhon\model\ModelArray;
use comhon\model\ModelEnum;
use comhon\model\ModelInteger;
use comhon\model\ModelFloat;
use comhon\model\ModelBoolean;
use comhon\model\ModelString;
use comhon\model\ModelDateTime;
use comhon\model\Model;
use comhon\model\MainModel;
use comhon\model\LocalModel;
use comhon\model\property\Property;
use comhon\model\ModelForeign;
use comhon\model\SimpleModel;
use comhon\model\property\ForeignProperty;
use comhon\model\property\AggregationProperty;
use comhon\object\config\Config;
use comhon\manifest\parser\ManifestParser;
use comhon\manifest\parser\SerializationManifestParser;
use comhon\manifest\parser\json\JsonManifestParser as ParentJsonManifestParser;

class JsonManifestParser extends ParentJsonManifestParser {

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
	
		$lName      = key($this->mCurrentProperties);
		$lIsId      = array_key_exists('is_id', $lCurrentPropertyJson) && $lCurrentPropertyJson['is_id'];
		$lIsPrivate = array_key_exists('is_private', $lCurrentPropertyJson) && $lCurrentPropertyJson['is_private'];
		$lModel     = $this->_completePropertyModel($lCurrentPropertyJson, $pPropertyModel);
		
		if (array_key_exists('xml', $lCurrentPropertyJson)) {
			$lType = $lCurrentPropertyJson['xml'];
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
		
		if (array_key_exists('default', $lCurrentPropertyJson)) {
			if ($lModel instanceof ModelDateTime) {
				$lDefault = $lCurrentPropertyJson['default'];
				if (new \DateTime($lDefault) === false) {
					throw new \Exception('invalid default value time format : '.$lDefault);
				}
			} else if (($lModel instanceof SimpleModel) || ($lModel instanceof ModelEnum)) {
				$lDefault = $lCurrentPropertyJson['default'];
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
			if ($pModel->getName() !== $lTypeId) {
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
				$pInstanceModels[$lType] = [$pManifestPath_ad.'/'.$lManifestPath_rfe, null];
			}
		}
	}
	
}