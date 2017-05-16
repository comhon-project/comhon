<?php

namespace comhon\manifest\parser\v_2_0;

use \Exception;
use comhon\model\ModelArray;
use comhon\model\ModelEnum;
use comhon\model\ModelDateTime;
use comhon\model\Model;
use comhon\model\property\Property;
use comhon\model\SimpleModel;
use comhon\manifest\parser\ManifestParser as ParentManifestParser;
use comhon\interfacer\XMLInterfacer;

class ManifestParser extends ParentManifestParser {

	public function getExtends() {
		if ($this->mFocusLocalTypes) {
			$lCurrent = current($this->mLocalTypes);
			return $this->mInterfacer->getValue($lCurrent, self::_EXTENDS);
		} else {
			return $this->mInterfacer->getValue($this->mManifest, self::_EXTENDS);
		}
	}
	
	public function getObjectClass() {
		if ($this->mFocusLocalTypes) {
			$lCurrent = current($this->mLocalTypes);
			return $this->mInterfacer->getValue($lCurrent, self::_OBJECT);
		} else {
			return $this->mInterfacer->getValue($this->mManifest, self::_OBJECT);
		}
	}
	
	public function getCurrentLocalTypeId() {
		$lCurrent = current($this->mLocalTypes);
		return $this->mInterfacer->getValue($lCurrent, self::NAME);
	}
	
	protected function _getLocalTypes() {
		return $this->mInterfacer->hasValue($this->mManifest, 'types', true)
			? $this->mInterfacer->getTraversableNode($this->mInterfacer->getValue($this->mManifest, 'types', true))
			: []; 
	}
	
	protected function _getCurrentProperties() {
		$lParentNode = $this->mFocusLocalTypes ? current($this->mLocalTypes) : $this->mManifest;
		return $this->mInterfacer->hasValue($lParentNode, 'properties', true)
			? $this->mInterfacer->getTraversableNode($this->mInterfacer->getValue($lParentNode, 'properties', true))
			: [];
	}
	
	public function getCurrentPropertyModelName() {
		return $this->_getPropertyModelName(current($this->mCurrentProperties));
	}
	
	private function _getPropertyModelName($pProperty) {
		$lModelName = $this->mInterfacer->getValue($pProperty, 'type');
		if ($lModelName == 'array') {
			$lModelName = $this->_getPropertyModelName($this->mInterfacer->getValue($pProperty, 'values', true));
		}
		return $lModelName;
	}
	
	/**
	 * @return string
	 */
	protected function _isCurrentPropertyForeign() {
		$lCurrentProperty = current($this->mCurrentProperties);
		
		return $this->mInterfacer->hasValue($lCurrentProperty, self::IS_FOREIGN)
			? (
				$this->mCastValues
					? $this->mInterfacer->castValueToBoolean($this->mInterfacer->getValue($lCurrentProperty, self::IS_FOREIGN))
					: $this->mInterfacer->getValue($lCurrentProperty, self::IS_FOREIGN)
			)
			: false;
	}
	
	protected function _getBaseInfosProperty(Model $pPropertyModel) {
		$lCurrentProperty = current($this->mCurrentProperties);
	
		$lIsId = $this->mInterfacer->hasValue($lCurrentProperty, self::IS_ID)
			? (
				$this->mCastValues
					? $this->mInterfacer->castValueToBoolean($this->mInterfacer->getValue($lCurrentProperty, self::IS_ID))
					: $this->mInterfacer->getValue($lCurrentProperty, self::IS_ID)
			)
			: false;
		
		$lIsPrivate = $this->mInterfacer->hasValue($lCurrentProperty, self::IS_PRIVATE)
			? (
				$this->mCastValues
					? $this->mInterfacer->castValueToBoolean($this->mInterfacer->getValue($lCurrentProperty, self::IS_PRIVATE))
					: $this->mInterfacer->getValue($lCurrentProperty, self::IS_PRIVATE)
			)
			: false;
		
		$lName      = $this->mInterfacer->getValue($lCurrentProperty, self::NAME);
		$lModel     = $this->_completePropertyModel($lCurrentProperty, $pPropertyModel);
		
		if ($this->mInterfacer->hasValue($lCurrentProperty, 'xml')) {
			$lType = $this->mInterfacer->getValue($lCurrentProperty, 'xml');
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
		
		return [$lName, $lModel, $lIsId, $lIsPrivate, $lInterfaceAsNodeXml];
	}
	
	/**
	 *
	 * @param Model $pPropertyModel
	 */
	protected function _getRestriction(Model $pPropertyModel) {
		$lCurrentProperty = current($this->mCurrentProperties);
		
		return ;
	}
	
	/**
	 *
	 * @param Model $pPropertyModel
	 * @return mixed|null
	 */
	protected function _getDefaultValue(Model $pPropertyModel) {
		$lCurrentProperty = current($this->mCurrentProperties);
		
		if ($this->mInterfacer->hasValue($lCurrentProperty, 'default')) {
			$lDefault = $this->mInterfacer->getValue($lCurrentProperty, 'default');
			if ($pPropertyModel instanceof ModelDateTime) {
				if (new \DateTime($lDefault) === false) {
					throw new \Exception('invalid default value time format : '.$lDefault);
				}
			} else if ($pPropertyModel instanceof SimpleModel) {
				$lDefault = $pPropertyModel->importSimple($lDefault, $this->mInterfacer);
			} else if ($pPropertyModel instanceof ModelEnum) {
				$lDefault = $pPropertyModel->getModel()->importSimple($lDefault, $this->mInterfacer);
			} else {
				throw new \Exception('default value can\'t be applied on complex model');
			}
		} else {
			$lDefault = null;
		}
		return $lDefault;
	}
	
	/**
	 * add model container if needed
	 * @param mixed $pPropertyNode
	 * @param Model $pModel
	 * @throws \Exception
	 * @return Model
	 */
	private function _completePropertyModel($pPropertyNode, $pModel) {
		$lPropertyModel = null;
		$lTypeId        = $this->mInterfacer->getValue($pPropertyNode,'type');
	
		if ($lTypeId == 'array') {
			$lValuesNode = $this->mInterfacer->getValue($pPropertyNode, 'values', true);
			if (is_null($lValuesNode)) {
				throw new \Exception('type array must have a values node');
			}
			$lValuesName = $this->mInterfacer->getValue($lValuesNode, 'name');
			if (is_null($lValuesName)) {
				throw new \Exception('type array must have a values name property');
			}
			$lPropertyModel = new ModelArray($this->_completePropertyModel($lValuesNode, $pModel), $lValuesName);
		}
		else {
			if ($pModel->getName() !== $lTypeId) {
				throw new \Exception('model doesn\'t match with type');
			}
			$lEnumNode = $this->mInterfacer->getValue($pPropertyNode, 'enum', true);
			if (is_null($lEnumNode)) {
				$lPropertyModel = $pModel;
			}
			else {
				$lList = $this->mInterfacer->getTraversableNode($lEnumNode);
				if ($this->mInterfacer instanceof XMLInterfacer) {
					foreach ($lList as $lName => $lDomNode) {
						$lList[$lName] = $this->mInterfacer->extractNodeText($lDomNode);
					}
				}
				$lPropertyModel = new ModelEnum($pModel, $lList);
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
		if ($this->mInterfacer->hasValue($this->mManifest, 'manifests', true)) {
			$lList = $this->mInterfacer->getTraversableNode($this->mInterfacer->getValue($this->mManifest, 'manifests', true), true);
			if ($this->mInterfacer instanceof XMLInterfacer) {
				foreach ($lList as $lName => $lDomNode) {
					$lList[$lName] = $this->mInterfacer->extractNodeText($lDomNode);
				}
			}
			foreach ($lList as $lType => $lManifestPath_rfe) {
				if (array_key_exists($lType, $pInstanceModels)) {
					throw new Exception("several model with same type : '$lType'");
				}
				$pInstanceModels[$lType] = [$pManifestPath_ad.'/'.$lManifestPath_rfe, null];
			}
		}
	}
	
}