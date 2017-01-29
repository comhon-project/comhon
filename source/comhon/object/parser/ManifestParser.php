<?php

namespace comhon\object\parser;

use comhon\object\parser\xml\XmlManifestParser;
use comhon\object\model\Model;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelForeign;
use comhon\object\model\ForeignProperty;
use comhon\object\model\AggregationProperty;
use comhon\object\model\Property;
use comhon\object\model\MainModel;
use comhon\object\parser\json\JsonManifestParser;
use comhon\object\model\MultipleForeignProperty;

abstract class ManifestParser {

	const _EXTENDS = 'extends';
	const _OBJECT  = 'object';

	protected $mManifest;
	protected $mSerializationManifestParser;

	protected $mFocusLocalTypes = false;
	protected $mLocalTypes;
	protected $mCurrentProperties;

	public abstract function getExtends();
	public abstract function getObjectClass();
	public abstract function getCurrentLocalTypeId();
	public abstract function getCurrentPropertyModelName();

	protected abstract function _verifManifest($pManifest);
	protected abstract function _getLocalTypes();
	protected abstract function _getCurrentProperties();
	protected abstract function _getBaseInfosProperty(Model $pPropertyModel);
	protected abstract function _isCurrentPropertyForeign();
	
	/**
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 */
	public final function __construct(Model $pModel, $pManifest, $pSerializationManifestPath_afe = null) {
		$this->_verifManifest($pManifest);
		$this->mManifest          = $pManifest;
		$this->mCurrentProperties = $this->_getCurrentProperties();
		$this->mLocalTypes        = $this->_getLocalTypes();
		
		if (empty($this->mCurrentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
		if (($pModel instanceof MainModel) && !is_null($pSerializationManifestPath_afe)) {
			$this->mSerializationManifestParser = SerializationManifestParser::getInstance($pModel, $pSerializationManifestPath_afe);
		}
	}
	
	public function getSerializationManifestParser() {
		return $this->mSerializationManifestParser;
	}
	
	public function getSerialization(MainModel $pModel) {
		return is_null($this->mSerializationManifestParser) ? null : $this->mSerializationManifestParser->getSerialization($pModel);
	}
	
	public function getLocalTypesCount() {
		return count($this->mLocalTypes);
	}
	
	public function isFocusOnLocalTypes() {
		return $this->mFocusLocalTypes;
	}
	
	public function activateFocusOnLocalTypes() {
		reset($this->mLocalTypes);
		$this->mFocusLocalTypes   = true;
		$this->mCurrentProperties = $this->_getCurrentProperties();
		
		if (empty($this->mCurrentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
	}
	
	public function desactivateFocusOnLocalTypes() {
		reset($this->mLocalTypes);
		$this->mFocusLocalTypes   = false;
		$this->mCurrentProperties = $this->_getCurrentProperties();
		
		if (empty($this->mCurrentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
	}
	
	/**
	 * go to next local type
	 * @return boolean false if cannot go to next element (typically when current element is the last)
	 */
	public function nextLocalType() {
		if ($this->mFocusLocalTypes && (next($this->mLocalTypes) !== false)) {
			$this->mCurrentProperties = $this->_getCurrentProperties();
			
			if (empty($this->mCurrentProperties)) {
				throw new \Exception('local type must have at least one property');
			}
			return true;
		}
		return false;
	}
	
	/**
	 * go to next property
	 * @return boolean false if cannot go to next element (typically when current element is the last)
	 */
	public function nextProperty() {
		return next($this->mCurrentProperties) !== false;
	}
	
	/**
	 * 
	 * @param Model $pPropertyModel
	 * @throws Exception
	 * @return Property
	 */
	public function getCurrentProperty(Model $pPropertyModel) {
		if ($this->_isCurrentPropertyForeign()) {
			list($lName, $lModel, $lIsId, $lIsPrivate, $lDefault) = $this->_getBaseInfosProperty($pPropertyModel);
			list($lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames) = $this->_getBaseSerializationInfosProperty($lName);
			
			$lModelForeign = new ModelForeign($lModel);
			if (!empty($lSerializationNames)) {
				if (count($lSerializationNames) < 2) {
					throw new \Exception('serializationNames must have at least two elements');
				}else if (!is_null($lSerializationName)) {
					throw new \Exception('serializationName and serializationNames cannot cohexist');
				} else if (!is_null($lAggregations)) {
					throw new \Exception('aggregation and serializationNames cannot cohexist');
				}
				$lProperty = new MultipleForeignProperty($lModelForeign, $lName, $lSerializationNames, $lIsPrivate, $lIsSerializable);
			}
			else if (is_null($lAggregations)) {
				$lProperty = new ForeignProperty($lModelForeign, $lName, $lSerializationName, $lIsPrivate, $lIsSerializable);
			} else {
				$lProperty = new AggregationProperty($lModelForeign, $lName, $lAggregations, $lSerializationName, $lIsPrivate);
			}
		}
		else {
			list($lName, $lModel, $lIsId, $lIsPrivate, $lDefault) = $this->_getBaseInfosProperty($pPropertyModel);
			list($lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames) = $this->_getBaseSerializationInfosProperty($lName);
			
			if (!empty($lSerializationNames)) {
				throw new \Exception('several serialization names only allowed for foreign properties');
			}
			
			$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId, $lIsPrivate, $lIsSerializable, $lDefault);
		}
		return $lProperty;
	}
	
	private function _getBaseSerializationInfosProperty($pPropertyName) {
		if (!$this->mFocusLocalTypes && !is_null($this->mSerializationManifestParser)) {
			return $this->mSerializationManifestParser->getPropertySerializationInfos($pPropertyName);
		}
		return [null, null, true, []];
	}
	
	/**
	 * register path of each manifest
	 * @param string $pManifestListPath_afe
	 * @param string $pSerializationListPath_afe
	 * @param array $pModelMap
	 * @throws \Exception
	 */
	public static function registerComplexModels($pManifestListPath_afe, $pSerializationListPath_afe, &$pModelMap) {
		
		switch (mb_strtolower(pathinfo($pSerializationListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$lSerializationMap = XmlManifestParser::_getSerializationMap($pSerializationListPath_afe);
				break;
			case 'json':
				$lSerializationMap = JsonManifestParser::_getSerializationMap($pSerializationListPath_afe);
				break;
			default:
				throw new \Exception('extension not recognized for serialization manifest list file : '.$pSerializationListPath_afe);
		}
		
		switch (mb_strtolower(pathinfo($pManifestListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				XmlManifestParser::_registerComplexModels($pManifestListPath_afe, $lSerializationMap, $pModelMap);
				break;
			case 'json':
				JsonManifestParser::_registerComplexModels($pManifestListPath_afe, $lSerializationMap, $pModelMap);
				break;
			default:
				throw new \Exception('extension not recognized for manifest list file : '.$pManifestListPath_afe);
		}
	}
	
	/**
	 * 
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getInstance(Model $pModel, $pManifestPath_afe, $pSerializationManifestPath_afe) {
		switch (mb_strtolower(pathinfo($pManifestPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				return XmlManifestParser::getVersionnedInstance($pModel, $pManifestPath_afe, $pSerializationManifestPath_afe);
				break;
			case 'json':
				return JsonManifestParser::getVersionnedInstance($pModel, $pManifestPath_afe, $pSerializationManifestPath_afe);
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$pManifestPath_afe);
		}
	}
	
}