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
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Exception\ComhonException;
use Comhon\Model\AbstractModel;
use Comhon\Model\Restriction\Restriction;
use Comhon\Model\Property\AutoProperty;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelUnique;
use Comhon\Exception\ArgumentException;

abstract class ManifestParser {

	/** @var string */
	const _EXTENDS        = 'extends';
	
	/** @var string */
	const OBJECT_CLASS    = 'object_class';
	
	/** @var string */
	const IS_MAIN         = 'is_main';
	
	/** @var string */
	const INHERITANCE_REQUESTABLES = 'inheritance_requestables';
	
	/** @var string */
	const NAME            = 'name';
	
	/** @var string */
	const IS_ID           = 'is_id';
	
	/** @var string */
	const IS_PRIVATE      = 'is_private';
	
	/** @var string */
	const IS_FOREIGN      = 'is_foreign';
	
	/** @var string */
	const IS_REQUIRED  = 'is_required';
	
	/** @var string */
	const IS_ASSOCIATIVE  = 'is_associative';
	
	/** @var string */
	const IS_ABSTRACT = 'is_abstract';
	
	/** @var string */
	const IS_ISOLATED = 'is_isolated';
	
	/** @var string */
	const DEPENDS = 'depends';
	
	/** @var string */
	const CONFLICTS = 'conflicts';
	
	/** @var string */
	const SHARE_PARENT_ID = 'share_parent_id';
	
	/** @var string */
	const SHARED_ID       = 'shared_id';
	
	/** @var string */
	const XML_ELEM_TYPE   = 'xml';
	
	/** @var string */
	const XML_NODE        = 'node';
	
	/** @var string */
	const XML_ATTRIBUTE   = 'attribute';
	
	/** @var string */
	const AUTO            = 'auto';
	
	/** @var string */
	const AGGREGATIONS = 'aggregations';
	
	// list of all restrictions
	
	/** @var string */
	const ENUM  = 'enum';
	
	/** @var string */
	const INTERVAL  = 'interval';
	
	/** @var string */
	const PATTERN  = 'pattern';
	
	/** @var string */
	const REGEX  = 'regex';
	
	/** @var string */
	const NOT_NULL  = 'not_null';
	
	/** @var string */
	const NOT_EMPTY  = 'not_empty';
	
	/** @var string */
	const SIZE  = 'size';
	
	/** @var string */
	const LENGTH  = 'length';
	
	/** @var string */
	const IS_MODEL_NAME  = 'is_model_name';
	
	/** @var mixed */
	protected $manifest;
	
	/** @var SerializationManifestParser */
	private $serializationManifestParser;
	
	/** @var boolean */
	private $serializationManifestParserInitialized = false;
	
	/** @var string */
	protected $serializationManifestPath_afe;
	
	/** @var \Comhon\Interfacer\Interfacer */
	protected $interfacer;
	
	/** @var boolean */
	protected $castValues;
	
	/** @var string */
	protected $namespace;
	
	/** @var boolean */
	protected $isLocal;
	
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
	 * get extends model names
	 *
	 * @return string[]|null null if no extends model name
	 */
	abstract public function getExtends();
	
	/**
	 * get inherited model that are requestable
	 *
	 * @return string[]|null null if no requestable inherited model
	 */
	abstract public function getInheritanceRequestable();
	
	/**
	 * get object class
	 * 
	 * @return string|null null if no associated class
	 */
	abstract public function getObjectClass();
	
	/**
	 * verify if manifest describe a model is abstract.
	 * object with abstract model may be instanciated instanciated but cannot be loaded and cannot be interfaced
	 *
	 * @return boolean
	 */
	abstract public function isAbstract();
	
	/**
	 * verify if manifest describe a model that share id with its direct parent model.
	 * if true, that mean it share id with first extends element.
	 * object with model that share id may be found in object collection with object model name or parent model name
	 *
	 * @return boolean
	 */
	abstract public function isSharedParentId();
	
	/**
	 * verify if manifest describe a model that share id with any parent model and get its parent model name.
	 * object with model that share id may be found in object collection with object model name or parent model name
	 *
	 * @return string|null if no model to share id with
	 */
	abstract public function sharedId();
	
	/**
	 * get manifest parsers that will permit to build all local models
	 *
	 * @return ManifestParser[]
	 */
	abstract public function getLocalModelManifestParsers();
	
	/**
	 * get name of unique model of current property
	 * 
	 * @return string
	 */
	abstract public function getCurrentPropertyModelUniqueName();

	/**
	 * get current properties
	 * 
	 * @return mixed[]
	 */
	abstract protected function _getCurrentProperties();
	
	/**
	 * get basic informations of property
	 * 
	 * @param \Comhon\Model\ModelUnique $propertyModelUnique unique model associated to property
	 * @return [string, \Comhon\Model\AbstractModel, boolean, boolean, boolean]
	 *     0 : property name
	 *     1 : final model associated to property
	 *     2 : true if property is id
	 *     3 : true if property is private
	 *     4 : true if property is interfaced as node xml
	 */
	abstract protected function _getBaseInfosProperty(ModelUnique $propertyModelUnique);
	
	/**
	 * get default value if exists
	 * 
	 * @param \Comhon\Model\AbstractModel $propertyModel
	 * @return mixed|null null if no default value
	 */
	abstract protected function _getDefaultValue(AbstractModel $propertyModel);
	
	/**
	 * get aggregation infos on current property
	 *
	 * @return string[]|null
	 */
	abstract protected function _getAggregationInfos();
	
	/**
	 * get properties values that MUST be set if current property value is set
	 *
	 * @return string[] empty if there is no dependencies
	 */
	abstract protected function _getDependencyProperties();
	
	/**
	 * get properties values that MUST NOT be set in same time
	 *
	 * @return string[] empty if there is no conflict
	 */
	abstract public function getConflicts();
	
	/**
	 * get Property/ComhonArray restrictions
	 * 
	 * @param mixed $currentNode
	 * @param \Comhon\Model\AbstractModel $propertyModel
	 * @return \Comhon\Model\Restriction\Restriction[]
	 */
	abstract protected function _getRestrictions($currentNode, AbstractModel $propertyModel);
	
	/**
	 * verify if current property is foreign
	 */
	abstract protected function _isCurrentPropertyForeign();
	
	/**
	 * @param mixed $manifest
	 * @param boolean $isLocal
	 * @param string $namespace
	 * @param string $serializationManifestPath_afe
	 * @param boolean $init
	 */
	final public function __construct($manifest, $isLocal, $namespace, $serializationManifestPath_afe = null, $init = true) {
		$this->manifest = $manifest;
		$this->isLocal = $isLocal;
		$this->namespace = $namespace;
		$this->serializationManifestPath_afe = $serializationManifestPath_afe;
		
		if ($init) {
			$this->_init();
		}
	}
	
	private function _init() {
		$this->interfacer = $this->_getInterfacer($this->manifest);
		$this->castValues = ($this->interfacer instanceof NoScalarTypedInterfacer);
	}
	
	/**
	 * get namespace used for current manifest
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}
	
	/**
	 * get serialization manifest parser
	 * 
	 * @return SerializationManifestParser
	 */
	public function getSerializationManifestParser() {
		if (!$this->serializationManifestParserInitialized) {
			if (file_exists($this->serializationManifestPath_afe)) {
				$this->serializationManifestParser = SerializationManifestParser::getInstance($this->serializationManifestPath_afe);
			}
			$this->serializationManifestParserInitialized = true;
		}
		return $this->serializationManifestParser;
	}
	
	/**
	 * go to next property
	 * 
	 * @return boolean false if there is no next property
	 */
	public function nextProperty() {
		if (is_null($this->currentProperties)) {
			$this->currentProperties = $this->_getCurrentProperties();
		}
		return next($this->currentProperties) !== false;
	}
	
	/**
	 * get manifest current property node
	 *
	 * @return mixed
	 */
	protected function _getCurrentPropertyNode() {
		if (is_null($this->currentProperties)) {
			$this->currentProperties = $this->_getCurrentProperties();
		}
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
		if (is_null($this->currentProperties)) {
			$this->currentProperties = $this->_getCurrentProperties();
		}
		return count($this->currentProperties);
	}
	
	/**
	 * get boolean value from manifest (cast if necessary)
	 * 
	 * @param mixed $node node
	 * @param string $name value's name
	 * @param boolean $defaultValue used if value not found
	 * @return boolean
	 */
	protected function _getBooleanValue($node, $name, $defaultValue) {
		return $this->interfacer->hasValue($node, $name)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($node, $name))
					: $this->interfacer->getValue($node, $name)
			)
			: $defaultValue;
	}
	
	/**
	 * 
	 * @param mixed $node node
	 * @param string $name value's name
	 * @return string[]
	 */
	public function _getArrayStringValue($node, $name) {
		if (!$this->interfacer->hasValue($node, $name, true)) {
			return [];
		}
		$values = $this->interfacer->getTraversableNode($this->interfacer->getValue($node, $name, true));
		if ($this->interfacer instanceof XMLInterfacer) {
			foreach ($values as $key => $domNode) {
				$values[$key] = $this->interfacer->extractNodeText($domNode);
			}
		}
		
		return $values;
	}
	
	/**
	 * get current property
	 * 
	 * @param \Comhon\Model\ModelUnique $propertyModelUnique unique model associated to property
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property
	 */
	public function getCurrentProperty(ModelUnique $propertyModelUnique) {
		list($name, 
			$model, 
			$isId, 
			$isPrivate, 
			$isNotNull,
			$isRequired, 
			$isIsolated, 
			$interfaceAsNodeXml, 
			$auto
		) = $this->_getBaseInfosProperty($propertyModelUnique);
		list($serializationName, $isSerializable, $serializationNames) = $this->_getBaseSerializationInfosProperty($name);
		$dependencies = $this->_getDependencyProperties();
		
		if ($this->_isCurrentPropertyForeign()) {
			$modelForeign = new ModelForeign($model);
			$aggregations = null;
			if (($model instanceof ModelArray) && $model->getDimensionsCount() == 1) {
				$aggregations = $this->_getAggregationInfos();
			}
			if (!empty($serializationNames)) {
				if (count($serializationNames) < 2) {
					throw new ManifestException('serializationNames must have at least two elements');
				}else if (!is_null($serializationName)) {
					throw new ManifestException('serializationName and serializationNames cannot coexist');
				} else if (!is_null($aggregations)) {
					throw new ManifestException('aggregation and serializationNames cannot coexist');
				}
				$property = new MultipleForeignProperty($modelForeign, $name, $serializationNames, $isPrivate, $isRequired, $isSerializable, $isNotNull, $dependencies);
			}
			else if (is_null($aggregations)) {
				$property = new ForeignProperty($modelForeign, $name, $serializationName, $isPrivate, $isRequired, $isSerializable, $isNotNull, $dependencies);
			} else {
				$property = new AggregationProperty($modelForeign, $name, $aggregations, $serializationName, $isPrivate, $dependencies);
			}
		}
		else {
			$default = $this->_getDefaultValue($model);
			$restrictions = $this->_getRestrictions($this->_getCurrentPropertyNode(), $model);
			
			if (!empty($serializationNames)) {
				throw new ManifestException('several serialization names only allowed for foreign properties');
			}
			if (is_null($auto)) {
				$property = new Property($model, $name, $serializationName, $isId, $isPrivate, $isRequired, $isSerializable, $isNotNull, $default, $interfaceAsNodeXml, $restrictions, $dependencies, $isIsolated);
				// verify default value (get it from property due to dateTime that need to instanciate DateTime object)
				if (!is_null($default) && !is_null($restriction = Restriction::getFirstNotSatisifed($restrictions, $property->getDefaultValue()))) {
					throw new NotSatisfiedRestrictionException($property->getDefaultValue(), $restriction);
				}
			} else {
				$property = new AutoProperty($model, $name, $serializationName, $isId, $isPrivate, $isRequired, $isSerializable, $interfaceAsNodeXml, $dependencies, $auto);
			}
		}
		return $property;
	}
	
	/**
	 * get serialization informations of property
	 * 
	 * @param string $propertyName
	 * @return [string|null, boolean, string[]|null]
	 *     0 : serialization name
	 *     1 : true if property is serializable
	 *     2 : serialization names if property is serialized in several properties
	 */
	private function _getBaseSerializationInfosProperty($propertyName) {
		if (!is_null($this->getSerializationManifestParser())) {
			return $this->getSerializationManifestParser()->getPropertySerializationInfos($propertyName);
		}
		return [null, true, []];
	}
	
	/**
	 * get manifest parser instance
	 * 
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 * @param string $namespace
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getInstance($manifestPath_afe, $serializationManifestPath_afe, $namespace) {
		try {
			$interfacer = Interfacer::getInstance(mb_strtolower(pathinfo($manifestPath_afe, PATHINFO_EXTENSION)), true);
		} catch (ArgumentException $e) {
			throw new ManifestException('extension not recognized for manifest file : '.$manifestPath_afe);
		}
		return self::_getInstanceWithInterfacer($manifestPath_afe, $serializationManifestPath_afe, $namespace, $interfacer);
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
	 * @param string $manifestPath_afe
	 * @param string $serializationManifestPath_afe
	 * @param string $namespace
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	private static function _getInstanceWithInterfacer($manifestPath_afe, $serializationManifestPath_afe, $namespace, Interfacer $interfacer) {
		$manifest = $interfacer->read($manifestPath_afe);
		
		if ($manifest === false || is_null($manifest)) {
			throw new ManifestException("manifest file not found or malformed '$manifestPath_afe'");
		}
		
		if (!$interfacer->hasValue($manifest, 'version')) {
			throw new ManifestException("manifest '$manifestPath_afe' doesn't have version");
		}
		$version = (string) $interfacer->getValue($manifest, 'version');
		switch ($version) {
			case '2.0': return new V_2_0\ManifestParser($manifest, false, $namespace, $serializationManifestPath_afe);
			case '3.0': return new V_3_0\ManifestParser($manifest, false, $namespace, $serializationManifestPath_afe);
			default:    throw new ManifestException("version $version not recognized for manifest $manifestPath_afe");
		}
	}
	
}