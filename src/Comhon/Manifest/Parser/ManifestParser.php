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
use Comhon\Exception\ManifestException;
use Comhon\Exception\UniqueModelNameException;

abstract class ManifestParser {

	/** @var string */
	const _EXTENDS   = 'extends';
	
	/** @var string */
	const _OBJECT    = 'object';
	
	/** @var string */
	const NAME          = 'name';
	
	/** @var string */
	const IS_ID         = 'is_id';
	
	/** @var string */
	const IS_PRIVATE    = 'is_private';
	
	/** @var string */
	const IS_FOREIGN    = 'is_foreign';
	
	/** @var string */
	const XML_NODE      = 'node';
	
	/** @var string */
	const XML_ATTRIBUTE = 'attribute';
	
	/** @var mixed */
	protected $manifest;
	
	/** @var SerializationManifestParser */
	protected $serializationManifestParser;
	
	/** @var \Comhon\Interfacer\Interfacer */
	protected $interfacer;
	
	/** @var boolean */
	protected $castValues;

	/** @var boolean */
	protected $focusLocalTypes = false;
	
	/** @var array */
	protected $localTypes;
	
	/** @var array */
	protected $currentProperties;

	/**
	 * get extends model name
	 *
	 * @return string|null null if no extends model name
	 */
	abstract public function getExtends();
	
	/**
	 * get object class
	 * 
	 * @return string|null null if no associated class
	 */
	abstract public function getObjectClass();
	
	/**
	 * get current local model name
	 * 
	 * @return string
	 */
	abstract public function getCurrentLocalModelName();
	
	/**
	 * get current property model name
	 * 
	 * @return string
	 */
	abstract public function getCurrentPropertyModelName();

	/**
	 * get local types
	 * 
	 * @return mixed[]
	 */
	abstract protected function _getLocalTypes();
	
	/**
	 * get current properties
	 * 
	 * @return mixed[]
	 */
	abstract protected function _getCurrentProperties();
	
	/**
	 * get basic informations of property
	 * 
	 * @param \Comhon\Model\Model $propertyModel unique model associated to property
	 * @return [string, \Comhon\Model\Model, boolean, boolean, boolean]
	 *     0 : property name
	 *     1 : final model associated to property
	 *     2 : true if property is id
	 *     3 : true if property is private
	 *     4 : true if property is interfaced as node xml
	 */
	abstract protected function _getBaseInfosProperty(Model $propertyModel);
	
	/**
	 * get default value if exists
	 * 
	 * @param \Comhon\Model\Model $propertyModel
	 * @return mixed|null null if no default value
	 */
	abstract protected function _getDefaultValue(Model $propertyModel);
	
	/**
	 * get property/ObjectArray restriction
	 * 
	 * @param mixed $currentNode
	 * @param \Comhon\Model\Model $propertyModel
	 */
	abstract protected function _getRestriction($currentNode, Model $propertyModel);
	
	/**
	 * verify if current property is foreign
	 */
	abstract protected function _isCurrentPropertyForeign();
	
	/**
	 * register complex local model
	 * 
	 * @param \Comhon\Model\Model[] $instanceModels
	 * @param string $manifestPath_ad
	 * @param string $namespace
	 */
	abstract public function registerComplexLocalModels(&$instanceModels, $manifestPath_ad, $namespace);
	
	/**
	 * @param \Comhon\Model\Model $model
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 */
	final public function __construct(Model $model, $manifest, $serializationManifestPath_afe = null) {
		$this->interfacer        = $this->_getInterfacer($manifest);
		$this->manifest          = $manifest;
		$this->currentProperties = $this->_getCurrentProperties();
		$this->localTypes        = $this->_getLocalTypes();
		$this->castValues        = ($this->interfacer instanceof NoScalarTypedInterfacer);
		
		if (empty($this->currentProperties)) {
			throw new ManifestException('manifest must have at least one property');
		}
		if (($model instanceof MainModel) && !is_null($serializationManifestPath_afe)) {
			$this->serializationManifestParser = SerializationManifestParser::getInstance($model, $serializationManifestPath_afe);
		}
	}
	
	/**
	 * get serialization manifest parser
	 * 
	 * @return SerializationManifestParser
	 */
	public function getSerializationManifestParser() {
		return $this->serializationManifestParser;
	}
	
	/**
	 * get local model count
	 * 
	 * @return integer
	 */
	public function getLocalModelCount() {
		return count($this->localTypes);
	}
	
	/**
	 * is currently focus on local model
	 * 
	 * @return boolean
	 */
	public function isFocusOnLocalModel() {
		return $this->focusLocalTypes;
	}
	
	/**
	 * activate focus on local models
	 * 
	 * @throws \Exception
	 */
	public function activateFocusOnLocalModels() {
		reset($this->localTypes);
		$this->focusLocalTypes   = true;
		$this->currentProperties = $this->_getCurrentProperties();
		
		if (empty($this->currentProperties)) {
			throw new ManifestException('manifest must have at least one property');
		}
	}
	
	/**
	 * desactivate focus on local models
	 *
	 * @throws \Exception
	 */
	public function desactivateFocusOnLocalModels() {
		reset($this->localTypes);
		$this->focusLocalTypes   = false;
		$this->currentProperties = $this->_getCurrentProperties();
		
		if (empty($this->currentProperties)) {
			throw new ManifestException('manifest must have at least one property');
		}
	}
	
	/**
	 * go to next local model
	 * 
	 * @throws \Exception
	 * @return boolean false if there is no next local model
	 */
	public function nextLocalModel() {
		if ($this->focusLocalTypes && (next($this->localTypes) !== false)) {
			$this->currentProperties = $this->_getCurrentProperties();
			
			if (empty($this->currentProperties)) {
				throw new ManifestException('local type must have at least one property');
			}
			return true;
		}
		return false;
	}
	
	/**
	 * go to next property
	 * 
	 * @return boolean false if there is no next property
	 */
	public function nextProperty() {
		return next($this->currentProperties) !== false;
	}
	
	/**
	 * get current property
	 * 
	 * @param \Comhon\Model\Model $propertyModel unique model associated to property
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property
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
					throw new ManifestException('serializationNames must have at least two elements');
				}else if (!is_null($serializationName)) {
					throw new ManifestException('serializationName and serializationNames cannot coexist');
				} else if (!is_null($aggregations)) {
					throw new ManifestException('aggregation and serializationNames cannot coexist');
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
				throw new ManifestException('several serialization names only allowed for foreign properties');
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
	
	/**
	 * get serialization informations of property
	 * 
	 * @param unknown $propertyName
	 * @return [string|null, \Comhon\Model\Property\Property[]|null, boolean, string[]|null]
	 *     0 : serialization name $serializationNames)
	 *     1 : aggregations
	 *     2 : true if property is serializable
	 *     3 : true if property is serialized in several properties
	 */
	private function _getBaseSerializationInfosProperty($propertyName) {
		if (!$this->focusLocalTypes && !is_null($this->serializationManifestParser)) {
			return $this->serializationManifestParser->getPropertySerializationInfos($propertyName);
		}
		return [null, null, true, []];
	}
	
	/**
	 * register path of each manifest
	 * 
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
	 * get manifest parser instance
	 * 
	 * @param \Comhon\Model\Model $model
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
				throw new ManifestException('extension not recognized for manifest file : '.$manifestPath_afe);
		}
		return self::_getInstanceWithInterfacer($model, $manifestPath_afe, $serializationManifestPath_afe, $interfacer);
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * 
	 * @param mixed $manifest
	 * @return \Comhon\Interfacer\Interfacer
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
		throw new ManifestException('not recognized manifest format');
	}
	
	/**
	 * register path of each manifest
	 * 
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
				throw new ManifestException('extension not recognized for manifest list file : '.$manifestListPath_afe);
		}
		
		$manifestList = $interfacer->read($manifestListPath_afe);
		if ($manifestList === false || is_null($manifestList)) {
			throw new ManifestException("manifestList file not found or malformed '$manifestListPath_afe'");
		}
		if (!$interfacer->hasValue($manifestList, 'version')) {
			throw new ManifestException("manifest list '$manifestListPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifestList, 'version');
		switch ($version) {
			case '2.0': self::_registerComplexModels_2_0($manifestList, $manifestListFolder_ad, $serializationMap, $modelMap, $interfacer); break;
			default:    throw new ManifestException("version $version not recognized for manifest list $manifestListPath_afe");
		}
	}
	
	/**
	 * register path of each manifest from manifest list version 2.0
	 * 
	 * @param mixed $manifestList
	 * @param string $manifestListFolder_ad
	 * @param string[] $serializationMap
	 * @param array $modelMap
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	protected static function _registerComplexModels_2_0($manifestList, $manifestListFolder_ad, $serializationMap, &$modelMap, Interfacer $interfacer) {
		$list = $interfacer->getTraversableNode($interfacer->getValue($manifestList, 'list', true), true);
		if (is_null($list)) {
			throw new ManifestException('malformed manifest list file, property \'list\' is missing');
		}
		if ($interfacer instanceof XMLInterfacer) {
			foreach ($list as $name => $domNode) {
				$list[$name] = $interfacer->extractNodeText($domNode);
			}
		}
		foreach ($list as $modelName => $manifestPath_rfe) {
			if (array_key_exists($modelName, $modelMap)) {
				throw new UniqueModelNameException($modelName);
			}
			$serializationPath_afe = array_key_exists($modelName, $serializationMap) ? $serializationMap[$modelName] : null;
			$modelMap[$modelName] = [$manifestListFolder_ad.'/'.$manifestPath_rfe, $serializationPath_afe];
		}
	}
	
	/**
	 * get serialization map 
	 * 
	 * each key is a model name and each value is the associated path to serialization manifest
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
				throw new ManifestException('extension not recognized for serialization manifest list file : '.$serializationListPath_afe);
		}
		
		$serializationList = $interfacer->read($serializationListPath_afe);
		if ($serializationList=== false || is_null($serializationList)) {
			throw new ManifestException("serializationList file not found or malformed '$serializationListPath_afe'");
		}
		if (!$interfacer->hasValue($serializationList, 'version')) {
			throw new ManifestException("serialization list '$serializationListPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($serializationList, 'version');
		switch ($version) {
			case '2.0': return self::_getSerializationMap_2_0($serializationList, $serializationListFolrder_ad, $interfacer);
			default:    throw new ManifestException("version $version not recognized for serialization list $serializationListPath_afe");
		}
	}
	
	/**
	 * get serialization map from manifest list version 2.0
	 * 
	 * each key is a model name and each value is the associated path to serialization manifest
	 * 
	 * @param mixed $serializationList
	 * @param string $serializationListFolrder_ad
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return string[]
	 */
	protected static function _getSerializationMap_2_0($serializationList, $serializationListFolrder_ad, Interfacer $interfacer) {
		$serializationMap = [];
		$list = $interfacer->getTraversableNode($interfacer->getValue($serializationList, 'list', true), true);
		if (is_null($list)) {
			throw new ManifestException('malformed serialization list file, property \'list\' is missing');
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
	 * get manifest parser instance
	 *
	 * @param \Comhon\Model\Model $model
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	private static function _getInstanceWithInterfacer($model, $manifestPath_afe, $serializationManifestPath_afe, Interfacer $interfacer) {
		$manifest = $interfacer->read($manifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new ManifestException("manifest file not found or malformed '$manifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new ManifestException("manifest '$manifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\ManifestParser($model, $manifest, $serializationManifestPath_afe, $interfacer);
			default:    throw new ManifestException("version $version not recognized for manifest $manifestPath_afe");
		}
	}
	
}