<?php

namespace comhon\manifest\parser;

use comhon\model\Model;
use comhon\model\ModelForeign;
use comhon\model\property\ForeignProperty;
use comhon\model\property\AggregationProperty;
use comhon\model\property\Property;
use comhon\model\MainModel;
use comhon\model\property\MultipleForeignProperty;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\Interfacer;
use comhon\interfacer\AssocArrayInterfacer;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\NoScalarTypedInterfacer;

abstract class ManifestParser {

	const _EXTENDS   = 'extends';
	const _OBJECT    = 'object';
	
	const NAME          = 'name';
	const IS_ID         = 'is_id';
	const IS_PRIVATE    = 'is_private';
	const IS_FOREIGN    = 'is_foreign';
	const XML_NODE      = 'node';
	const XML_ATTRIBUTE = 'attribute';

	protected $mManifest;
	protected $mSerializationManifestParser;
	protected $mInterfacer;
	protected $mCastValues;

	protected $mFocusLocalTypes = false;
	protected $mLocalTypes;
	protected $mCurrentProperties;

	abstract public function getExtends();
	abstract public function getObjectClass();
	abstract public function getCurrentLocalTypeId();
	abstract public function getCurrentPropertyModelName();

	abstract protected function _getLocalTypes();
	abstract protected function _getCurrentProperties();
	abstract protected function _getBaseInfosProperty(Model $pPropertyModel);
	abstract protected function _getDefaultValue(Model $pPropertyModel);
	abstract protected function _getRestriction(Model $pPropertyModel);
	abstract protected function _isCurrentPropertyForeign();
	
	/**
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 */
	public final function __construct(Model $pModel, $pManifest, $pSerializationManifestPath_afe = null) {
		$this->mInterfacer        = $this->_getInterfacer($pManifest);
		$this->mManifest          = $pManifest;
		$this->mCurrentProperties = $this->_getCurrentProperties();
		$this->mLocalTypes        = $this->_getLocalTypes();
		$this->mCastValues        = ($this->mInterfacer instanceof NoScalarTypedInterfacer);
		
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
			list($lName, $lModel, $lIsId, $lIsPrivate, $lInterfaceAsNodeXml) = $this->_getBaseInfosProperty($pPropertyModel);
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
			list($lName, $lModel, $lIsId, $lIsPrivate, $lInterfaceAsNodeXml) = $this->_getBaseInfosProperty($pPropertyModel);
			list($lSerializationName, $lAggregations, $lIsSerializable, $lSerializationNames) = $this->_getBaseSerializationInfosProperty($lName);
			
			$lDefault = $this->_getDefaultValue($lModel);
			$lRestriction = $this->_getRestriction($lModel);
			
			if (!empty($lSerializationNames)) {
				throw new \Exception('several serialization names only allowed for foreign properties');
			}
			$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId, $lIsPrivate, $lIsSerializable, $lDefault, $lInterfaceAsNodeXml, $lRestriction);
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
		$lSerializationMap = self::_getSerializationMap($pSerializationListPath_afe);
		self::_registerComplexModels($pManifestListPath_afe, $lSerializationMap, $pModelMap);
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
				$lInterfacer = new XMLInterfacer();
				break;
			case 'json':
				$lInterfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$pManifestPath_afe);
		}
		return self::getVersionnedInstance($pModel, $pManifestPath_afe, $pSerializationManifestPath_afe, $lInterfacer);
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * @param [] $pManifest
	 */
	public function _getInterfacer($pManifest) {
		if (is_array($pManifest)) {
			return new AssocArrayInterfacer();
		}
		if ($pManifest instanceof \stdClass) {
			return new StdObjectInterfacer();
		}
		if ($pManifest instanceof \DOMElement) {
			return new XMLInterfacer();
		}
		throw new \Exception('not recognized manifest format');
	}
	
	/**
	 * register path of each manifest
	 * @param string $pManifestListPath_afe
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 * @throws \Exception
	 */
	protected static function _registerComplexModels($pManifestListPath_afe, $pSerializationMap, &$pModelMap) {
		$lManifestListFolder_ad = dirname($pManifestListPath_afe);
		
		switch (mb_strtolower(pathinfo($pManifestListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$lInterfacer = new XMLInterfacer();
				break;
			case 'json':
				$lInterfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest list file : '.$pManifestListPath_afe);
		}
		
		$lManifestList = $lInterfacer->read($pManifestListPath_afe);
		if ($lManifestList === false || is_null($lManifestList)) {
			throw new \Exception("manifestList file not found or malformed '$pManifestListPath_afe'");
		}
		if (!$lInterfacer->hasValue($lManifestList, 'version')) {
			throw new \Exception("manifest list '$pManifestListPath_afe' doesn't have version");
		}
		$lVersion = (string) $lInterfacer->getValue($lManifestList, 'version');
		switch ($lVersion) {
			case '2.0': return self::_registerComplexModels_2_0($lManifestList, $lManifestListFolder_ad, $pSerializationMap, $pModelMap, $lInterfacer);
			default:    throw new \Exception("version $lVersion not recognized for manifest list $pManifestListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $pManifestList
	 * @param string $pManifestListFolder_ad
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 * @param Interfacer $pInterfacer
	 */
	protected static function _registerComplexModels_2_0($pManifestList, $pManifestListFolder_ad, $pSerializationMap, &$pModelMap, Interfacer $pInterfacer) {
		$lList = $pInterfacer->getTraversableNode($pInterfacer->getValue($pManifestList, 'list', true), true);
		if (is_null($lList)) {
			throw new \Exception('malformed manifest list file, property \'list\' is missing');
		}
		if ($pInterfacer instanceof XMLInterfacer) {
			foreach ($lList as $lName => $lDomNode) {
				$lList[$lName] = $pInterfacer->extractNodeText($lDomNode);
			}
		}
		foreach ($lList as $lModelName => $lManifestPath_rfe) {
			if (array_key_exists($lModelName, $pModelMap)) {
				throw new Exception("several model with same type : '$lModelName'");
			}
			$lSerializationPath_afe = array_key_exists($lModelName, $pSerializationMap) ? $pSerializationMap[$lModelName] : null;
			$pModelMap[$lModelName] = [$pManifestListFolder_ad.'/'.$lManifestPath_rfe, $lSerializationPath_afe];
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
		
		switch (mb_strtolower(pathinfo($pSerializationListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$lInterfacer = new XMLInterfacer();
				break;
			case 'json':
				$lInterfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for serialization manifest list file : '.$pSerializationListPath_afe);
		}
		
		$lSerializationList = $lInterfacer->read($pSerializationListPath_afe);
		if ($lSerializationList === false || is_null($lSerializationList)) {
			throw new \Exception("serializationList file not found or malformed '$pSerializationListPath_afe'");
		}
		if (!$lInterfacer->hasValue($lSerializationList, 'version')) {
			throw new \Exception("serialization list '$pSerializationListPath_afe' doesn't have version");
		}
		$lVersion = (string) $lInterfacer->getValue($lSerializationList, 'version');
		switch ($lVersion) {
			case '2.0': return self::_getSerializationMap_2_0($lSerializationList, $pSerializationListFolrder_ad, $lInterfacer);
			default:    throw new \Exception("version $lVersion not recognized for serialization list $pSerializationListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $pSerializationList
	 * @param string $pSerializationListFolrder_ad
	 * @param Interfacer $pInterfacer
	 * @return string[]
	 */
	protected static function _getSerializationMap_2_0($pSerializationList, $pSerializationListFolrder_ad, Interfacer $pInterfacer) {
		$lSerializationMap = [];
		$lList = $pInterfacer->getTraversableNode($pInterfacer->getValue($pSerializationList, 'list', true), true);
		if (is_null($lList)) {
			throw new \Exception('malformed serialization list file, property \'list\' is missing');
		}
		if ($pInterfacer instanceof XMLInterfacer) {
			foreach ($lList as $lName => $lDomNode) {
				$lList[$lName] = $pInterfacer->extractNodeText($lDomNode);
			}
		}
		foreach ($lList as $lModelName => $lSerializationPath_rfe) {
			$lSerializationMap[$lModelName] = $pSerializationListFolrder_ad.'/'.$lSerializationPath_rfe;
		}
		return $lSerializationMap;
	}
	
	/**
	 *
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($pModel, $pManifestPath_afe, $pSerializationManifestPath_afe, Interfacer $pInterfacer) {
		$lManifest = $pInterfacer->read($pManifestPath_afe);
		
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("manifest file not found or malformed '$pManifestPath_afe'");
		}
		
		if (!$pInterfacer->hasValue($lManifest, 'version')) {
			throw new \Exception("manifest '$pManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $pInterfacer->getValue($lManifest, 'version');
		switch ($lVersion) {
			case '2.0': return new v_2_0\ManifestParser($pModel, $lManifest, $pSerializationManifestPath_afe, $pInterfacer);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pManifestPath_afe");
		}
	}
	
}