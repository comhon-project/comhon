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
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Model\Property\RestrictedProperty;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Manifest\ReservedWordException;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Exception\ComhonException;
use Comhon\Model\AbstractModel;

abstract class ManifestParser {

	/** @var string */
	const _EXTENDS        = 'extends';
	
	/** @var string */
	const _OBJECT         = 'object';
	
	/** @var string */
	const IS_MAIN         = 'is_main';
	
	/** @var string */
	const IS_SERIALIZABLE = 'is_serializable';
	
	/** @var string */
	const NAME            = 'name';
	
	/** @var string */
	const IS_ID           = 'is_id';
	
	/** @var string */
	const IS_PRIVATE      = 'is_private';
	
	/** @var string */
	const IS_FOREIGN      = 'is_foreign';
	
	/** @var string */
	const IS_ASSOCIATIVE  = 'is_associative';
	
	/** @var string */
	const XML_NODE        = 'node';
	
	/** @var string */
	const XML_ATTRIBUTE   = 'attribute';
	
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
	private $currentProperties;

	/**
	 * verify if manifest describe a main model.
	 * if true that means comhon object with described model might be stored in MainObjectCollection
	 *
	 * @return boolean
	 */
	abstract public function isMain();
	
	/**
	 * verify if manifest describe a serializable model .
	 * a serializable model is automatically a main model
	 *
	 * @return boolean
	 */
	abstract public function isSerializable();
	
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
	 * @param \Comhon\Model\AbstractModel $propertyModel unique model associated to property
	 * @return [string, \Comhon\Model\AbstractModel, boolean, boolean, boolean]
	 *     0 : property name
	 *     1 : final model associated to property
	 *     2 : true if property is id
	 *     3 : true if property is private
	 *     4 : true if property is interfaced as node xml
	 */
	abstract protected function _getBaseInfosProperty(AbstractModel $propertyModel);
	
	/**
	 * get default value if exists
	 * 
	 * @param \Comhon\Model\AbstractModel $propertyModel
	 * @return mixed|null null if no default value
	 */
	abstract protected function _getDefaultValue(AbstractModel $propertyModel);
	
	/**
	 * get property/ComhonArray restriction
	 * 
	 * @param mixed $currentNode
	 * @param \Comhon\Model\AbstractModel $propertyModel
	 */
	abstract protected function _getRestriction($currentNode, AbstractModel $propertyModel);
	
	/**
	 * verify if current property is foreign
	 */
	abstract protected function _isCurrentPropertyForeign();
	
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
		
		if ($this->isSerializable() && !is_null($serializationManifestPath_afe)) {
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
	 * get manifest current property node
	 *
	 * @return mixed
	 */
	protected function _getCurrentPropertyNode() {
		if (!current($this->currentProperties)) {
			throw new ComhonException('current property is out of range');
		}
		return current($this->currentProperties);
	}
	
	/**
	 * get manifest current properties count
	 *
	 * @return integer
	 */
	public function getCurrentPropertiesCount() {
		return count($this->currentProperties);
	}
	
	/**
	 * get current property
	 * 
	 * @param \Comhon\Model\AbstractModel $propertyModel unique model associated to property
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property
	 */
	public function getCurrentProperty(AbstractModel $propertyModel) {
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
			$restriction = $this->_getRestriction($this->_getCurrentPropertyNode(), $model);
			
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