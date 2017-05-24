<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser;

use Comhon\Model\Model;
use Comhon\Model\ModelForeign;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Model\Property\Property;
use Comhon\Model\MainModel;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Model\Property\RestrictedProperty;
use Comhon\Exception\NotSatisfiedRestrictionException;
use Comhon\Exception\ReservedWordException;

abstract class ManifestParser {

	const _EXTENDS   = 'extends';
	const _OBJECT    = 'object';
	
	const NAME          = 'name';
	const IS_ID         = 'is_id';
	const IS_PRIVATE    = 'is_private';
	const IS_FOREIGN    = 'is_foreign';
	const XML_NODE      = 'node';
	const XML_ATTRIBUTE = 'attribute';

	protected $manifest;
	protected $serializationManifestParser;
	protected $interfacer;
	protected $castValues;

	protected $focusLocalTypes = false;
	protected $localTypes;
	protected $currentProperties;

	abstract public function getExtends();
	abstract public function getObjectClass();
	abstract public function getCurrentLocalTypeId();
	abstract public function getCurrentPropertyModelName();

	abstract protected function _getLocalTypes();
	abstract protected function _getCurrentProperties();
	abstract protected function _getBaseInfosProperty(Model $propertyModel);
	abstract protected function _getDefaultValue(Model $propertyModel);
	abstract protected function _getRestriction($currentNode, Model $propertyModel);
	abstract protected function _isCurrentPropertyForeign();
	
	/**
	 * @param Model $model
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 */
	public final function __construct(Model $model, $manifest, $serializationManifestPath_afe = null) {
		$this->interfacer        = $this->_getInterfacer($manifest);
		$this->manifest          = $manifest;
		$this->currentProperties = $this->_getCurrentProperties();
		$this->localTypes        = $this->_getLocalTypes();
		$this->castValues        = ($this->interfacer instanceof NoScalarTypedInterfacer);
		
		if (empty($this->currentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
		if (($model instanceof MainModel) && !is_null($serializationManifestPath_afe)) {
			$this->serializationManifestParser = SerializationManifestParser::getInstance($model, $serializationManifestPath_afe);
		}
	}
	
	public function getSerializationManifestParser() {
		return $this->serializationManifestParser;
	}
	
	public function getLocalTypesCount() {
		return count($this->localTypes);
	}
	
	public function isFocusOnLocalTypes() {
		return $this->focusLocalTypes;
	}
	
	public function activateFocusOnLocalTypes() {
		reset($this->localTypes);
		$this->focusLocalTypes   = true;
		$this->currentProperties = $this->_getCurrentProperties();
		
		if (empty($this->currentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
	}
	
	public function desactivateFocusOnLocalTypes() {
		reset($this->localTypes);
		$this->focusLocalTypes   = false;
		$this->currentProperties = $this->_getCurrentProperties();
		
		if (empty($this->currentProperties)) {
			throw new \Exception('manifest must have at least one property');
		}
	}
	
	/**
	 * go to next local type
	 * @return boolean false if cannot go to next element (typically when current element is the last)
	 */
	public function nextLocalType() {
		if ($this->focusLocalTypes && (next($this->localTypes) !== false)) {
			$this->currentProperties = $this->_getCurrentProperties();
			
			if (empty($this->currentProperties)) {
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
		return next($this->currentProperties) !== false;
	}
	
	/**
	 * 
	 * @param Model $propertyModel
	 * @throws Exception
	 * @return Property
	 */
	public function getCurrentProperty(Model $propertyModel) {
		if ($this->_isCurrentPropertyForeign()) {
			list($name, $model, $isId, $isPrivate, $interfaceAsNodeXml) = $this->_getBaseInfosProperty($propertyModel);
			list($serializationName, $aggregations, $isSerializable, $serializationNames) = $this->_getBaseSerializationInfosProperty($name);
			
			if ($name === Interfacer::INHERITANCE_KEY || $serializationName === Interfacer::INHERITANCE_KEY) {
				throw new ReservedWordException(Interfacer::INHERITANCE_KEY);
			}
			$modelForeign = new ModelForeign($model);
			if (!empty($serializationNames)) {
				if (count($serializationNames) < 2) {
					throw new \Exception('serializationNames must have at least two elements');
				}else if (!is_null($serializationName)) {
					throw new \Exception('serializationName and serializationNames cannot cohexist');
				} else if (!is_null($aggregations)) {
					throw new \Exception('aggregation and serializationNames cannot cohexist');
				}
				$property = new MultipleForeignProperty($modelForeign, $name, $serializationNames, $isPrivate, $isSerializable);
			}
			else if (is_null($aggregations)) {
				$property = new ForeignProperty($modelForeign, $name, $serializationName, $isPrivate, $isSerializable);
			} else {
				$property = new AggregationProperty($modelForeign, $name, $aggregations, $serializationName, $isPrivate);
			}
		}
		else {
			list($name, $model, $isId, $isPrivate, $interfaceAsNodeXml) = $this->_getBaseInfosProperty($propertyModel);
			list($serializationName, $aggregations, $isSerializable, $serializationNames) = $this->_getBaseSerializationInfosProperty($name);
			
			if ($name === Interfacer::INHERITANCE_KEY || $serializationName === Interfacer::INHERITANCE_KEY) {
				throw new ReservedWordException(Interfacer::INHERITANCE_KEY);
			}
			$default = $this->_getDefaultValue($model);
			$restriction = $this->_getRestriction(current($this->currentProperties), $model);
			
			if (!empty($serializationNames)) {
				throw new \Exception('several serialization names only allowed for foreign properties');
			}
			if (is_null($restriction)) {
				$property = new Property($model, $name, $serializationName, $isId, $isPrivate, $isSerializable, $default, $interfaceAsNodeXml);
			} else {
				$property = new RestrictedProperty($model, $name, $restriction, $serializationName, $isId, $isPrivate, $isSerializable, $default, $interfaceAsNodeXml);
				// verify default value
				// get it from property due to dateTime that need to instanciate DateTime object
				if (!is_null($default) && !$restriction->satisfy($property->getDefaultValue())) {
					throw new NotSatisfiedRestrictionException($property->getDefaultValue(), $restriction);
				}
			}
		}
		return $property;
	}
	
	private function _getBaseSerializationInfosProperty($propertyName) {
		if (!$this->focusLocalTypes && !is_null($this->serializationManifestParser)) {
			return $this->serializationManifestParser->getPropertySerializationInfos($propertyName);
		}
		return [null, null, true, []];
	}
	
	/**
	 * register path of each manifest
	 * @param string $manifestListPath_afe
	 * @param string $serializationListPath_afe
	 * @param array $modelMap
	 * @throws \Exception
	 */
	public static function registerComplexModels($manifestListPath_afe, $serializationListPath_afe, &$modelMap) {
		$serializationMap = self::_getSerializationMap($serializationListPath_afe);
		self::_registerComplexModels($manifestListPath_afe, $serializationMap, $modelMap);
	}
	
	/**
	 * 
	 * @param Model $model
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getInstance(Model $model, $manifestPath_afe, $serializationManifestPath_afe) {
		switch (mb_strtolower(pathinfo($manifestPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$manifestPath_afe);
		}
		return self::getVersionnedInstance($model, $manifestPath_afe, $serializationManifestPath_afe, $interfacer);
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * @param [] $manifest
	 */
	public function _getInterfacer($manifest) {
		if (is_array($manifest)) {
			return new AssocArrayInterfacer();
		}
		if ($manifest instanceof \stdClass) {
			return new StdObjectInterfacer();
		}
		if ($manifest instanceof \DOMElement) {
			return new XMLInterfacer();
		}
		throw new \Exception('not recognized manifest format');
	}
	
	/**
	 * register path of each manifest
	 * @param string $manifestListPath_afe
	 * @param string[] $serializationMap
	 * @param array $modelMap
	 * @throws \Exception
	 */
	protected static function _registerComplexModels($manifestListPath_afe, $serializationMap, &$modelMap) {
		$manifestListFolder_ad = dirname($manifestListPath_afe);
		
		switch (mb_strtolower(pathinfo($manifestListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest list file : '.$manifestListPath_afe);
		}
		
		$manifestList = $interfacer->read($manifestListPath_afe);
		if ($manifestList === false || is_null($manifestList)) {
			throw new \Exception("manifestList file not found or malformed '$manifestListPath_afe'");
		}
		if (!$interfacer->hasValue($manifestList, 'version')) {
			throw new \Exception("manifest list '$manifestListPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifestList, 'version');
		switch ($version) {
			case '2.0': return self::_registerComplexModels_2_0($manifestList, $manifestListFolder_ad, $serializationMap, $modelMap, $interfacer);
			default:    throw new \Exception("version $version not recognized for manifest list $manifestListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $manifestList
	 * @param string $manifestListFolder_ad
	 * @param string[] $serializationMap
	 * @param array $modelMap
	 * @param Interfacer $interfacer
	 */
	protected static function _registerComplexModels_2_0($manifestList, $manifestListFolder_ad, $serializationMap, &$modelMap, Interfacer $interfacer) {
		$list = $interfacer->getTraversableNode($interfacer->getValue($manifestList, 'list', true), true);
		if (is_null($list)) {
			throw new \Exception('malformed manifest list file, property \'list\' is missing');
		}
		if ($interfacer instanceof XMLInterfacer) {
			foreach ($list as $name => $domNode) {
				$list[$name] = $interfacer->extractNodeText($domNode);
			}
		}
		foreach ($list as $modelName => $manifestPath_rfe) {
			if (array_key_exists($modelName, $modelMap)) {
				throw new Exception("several model with same type : '$modelName'");
			}
			$serializationPath_afe = array_key_exists($modelName, $serializationMap) ? $serializationMap[$modelName] : null;
			$modelMap[$modelName] = [$manifestListFolder_ad.'/'.$manifestPath_rfe, $serializationPath_afe];
		}
	}
	
	/**
	 *
	 * @param string $serializationListPath_afe
	 * @throws \Exception
	 * @return string[]
	 */
	protected static function _getSerializationMap($serializationListPath_afe) {
		$serializationMap = [];
		$serializationListFolrder_ad = dirname($serializationListPath_afe);
		
		switch (mb_strtolower(pathinfo($serializationListPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for serialization manifest list file : '.$serializationListPath_afe);
		}
		
		$serializationList = $interfacer->read($serializationListPath_afe);
		if ($serializationList=== false || is_null($serializationList)) {
			throw new \Exception("serializationList file not found or malformed '$serializationListPath_afe'");
		}
		if (!$interfacer->hasValue($serializationList, 'version')) {
			throw new \Exception("serialization list '$serializationListPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($serializationList, 'version');
		switch ($version) {
			case '2.0': return self::_getSerializationMap_2_0($serializationList, $serializationListFolrder_ad, $interfacer);
			default:    throw new \Exception("version $version not recognized for serialization list $serializationListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $serializationList
	 * @param string $serializationListFolrder_ad
	 * @param Interfacer $interfacer
	 * @return string[]
	 */
	protected static function _getSerializationMap_2_0($serializationList, $serializationListFolrder_ad, Interfacer $interfacer) {
		$serializationMap = [];
		$list = $interfacer->getTraversableNode($interfacer->getValue($serializationList, 'list', true), true);
		if (is_null($list)) {
			throw new \Exception('malformed serialization list file, property \'list\' is missing');
		}
		if ($interfacer instanceof XMLInterfacer) {
			foreach ($list as $name => $domNode) {
				$list[$name] = $interfacer->extractNodeText($domNode);
			}
		}
		foreach ($list as $modelName => $serializationPath_rfe) {
			$serializationMap[$modelName] = $serializationListFolrder_ad.'/'.$serializationPath_rfe;
		}
		return $serializationMap;
	}
	
	/**
	 *
	 * @param Model $model
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($model, $manifestPath_afe, $serializationManifestPath_afe, Interfacer $interfacer) {
		$manifest = $interfacer->read($manifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new \Exception("manifest file not found or malformed '$manifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new \Exception("manifest '$manifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\ManifestParser($model, $manifest, $serializationManifestPath_afe, $interfacer);
			default:    throw new \Exception("version $version not recognized for manifest $manifestPath_afe");
		}
	}
	
}