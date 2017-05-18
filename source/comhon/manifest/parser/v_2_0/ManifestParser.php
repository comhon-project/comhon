<?php

namespace comhon\manifest\parser\v_2_0;

use \Exception;
use comhon\model\ModelArray;
use comhon\model\ModelDateTime;
use comhon\model\Model;
use comhon\model\property\Property;
use comhon\model\SimpleModel;
use comhon\manifest\parser\ManifestParser as ParentManifestParser;
use comhon\interfacer\XMLInterfacer;
use comhon\model\ModelString;
use comhon\model\ModelFloat;
use comhon\model\ModelInteger;
use comhon\model\restriction\Enum;
use comhon\model\restriction\Interval;
use comhon\model\restriction\Regex;
use comhon\model\ModelContainer;
use comhon\model\ModelRestrictedArray;

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
	 * @param mixed $pCurrentNode
	 * @param Model $pUniqueModel
	 */
	protected function _getRestriction($pCurrentNode, Model $pUniqueModel) {
		if ($pUniqueModel instanceof ModelContainer) {
			return null;
		}
		$lRestriction  = null;
		
		if ($this->mInterfacer->hasValue($pCurrentNode, 'enum', true)) {
			$lEnumValues = $this->mInterfacer->getTraversableNode($this->mInterfacer->getValue($pCurrentNode, 'enum', true));
			if ($this->mInterfacer instanceof XMLInterfacer) {
				if ($pUniqueModel instanceof ModelInteger) {
					foreach ($lEnumValues as $lDomNode) {
						$lEnumValues[] = $this->mInterfacer->extractNodeText($lDomNode);
					}
				} elseif (($pUniqueModel instanceof ModelString) || ($pUniqueModel instanceof ModelFloat)) {
					foreach ($lEnumValues as $lDomNode) {
						$lEnumValues[] = (integer) $this->mInterfacer->extractNodeText($lDomNode);
					}
				} else {
					throw new \Exception('enum cannot be defined on '.$pUniqueModel->getName());
				}
			}
			$lRestriction = new Enum($lEnumValues);
		}
		elseif ($this->mInterfacer->hasValue($pCurrentNode, 'interval')) {
			$lRestriction = new Interval($this->mInterfacer->getValue($pCurrentNode, 'interval'), $pUniqueModel);
			
		}
		elseif ($this->mInterfacer->hasValue($pCurrentNode, 'regex')) {
			if (!($pUniqueModel instanceof ModelString)) {
				throw new \Exception('regex cannot be defined on '.$pUniqueModel->getName());
			}
			$lRestriction = new Regex($this->mInterfacer->getValue($pCurrentNode, 'regex'));
		}
		
		return $lRestriction;
	}
	
	/**
	 *
	 * @param Model $pUniqueModel
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
	private function _completePropertyModel($pPropertyNode, $pUniqueModel) {
		$lPropertyModel = $pUniqueModel;
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
			$lSubModel = $this->_completePropertyModel($lValuesNode, $pUniqueModel);
			$lRestriction = $this->_getRestriction($lValuesNode, $pUniqueModel);
			if (is_null($lRestriction)) {
				$lPropertyModel = new ModelArray($lSubModel, $lValuesName);
			} else {
				$lPropertyModel = new ModelRestrictedArray($lSubModel, $lRestriction, $lValuesName);
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