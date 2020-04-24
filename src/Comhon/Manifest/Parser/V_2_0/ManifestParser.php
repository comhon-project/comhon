<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser\V_2_0;

use Comhon\Model\ModelArray;
use Comhon\Model\ModelDateTime;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelString;
use Comhon\Model\ModelFloat;
use Comhon\Model\ModelInteger;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\Restriction\Interval;
use Comhon\Model\Restriction\Regex;
use Comhon\Manifest\Parser\ManifestParser as ParentManifestParser;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Model\AbstractModel;
use Comhon\Model\Restriction\Size;
use Comhon\Model\Restriction\Length;
use Comhon\Model\Restriction\NotEmptyString;
use Comhon\Model\Restriction\NotEmptyArray;
use Comhon\Model\Restriction\ModelName;
use Comhon\Model\Restriction\RegexCollection;

class ManifestParser extends ParentManifestParser {
	
	/** @var string */
	const OBJECT_CLASS    = 'object';
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getExtends()
	 */
	public function getExtends() {
		$extends = $this->interfacer->getValue($this->manifest, self::_EXTENDS);
		
		return is_null($extends) ? null : [$extends];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getInheritanceRequestable()
	 */
	public function getInheritanceRequestable() {
		return $this->_getArrayStringValue($this->manifest, self::INHERITANCE_REQUESTABLES);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::isMain()
	 */
	public function isMain() {
		return $this->_getBooleanValue($this->manifest, self::IS_MAIN, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getObjectClass()
	 */
	public function getObjectClass() {
		return $this->interfacer->getValue($this->manifest, static::OBJECT_CLASS);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::isAbstract()
	 */
	public function isAbstract() {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::isSharedParentId()
	 */
	public function isSharedParentId() {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::sharedId()
	 */
	public function sharedId() {
		return null;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getLocalModelManifestParsers()
	 */
	public function getLocalModelManifestParsers() {
		$manifestParsers = [];
		$types = !$this->isLocal && $this->interfacer->hasValue($this->manifest, 'types', true)
			? $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, 'types', true))
			: []; 
		
		// don't use basename() because very slow compare to strrpos() + substr()
		$pos = strrpos($this->serializationManifestPath_afe, DIRECTORY_SEPARATOR);
		$dirname = substr($this->serializationManifestPath_afe, 0, $pos);
		$basename = substr($this->serializationManifestPath_afe, $pos + 1);
		
		foreach ($types as $type) {
			if (!$this->interfacer->hasValue($type, self::NAME)) {
				throw new ManifestException("local type name not defined");
			}
			$name = $this->interfacer->getValue($type, self::NAME);
			if (!is_string($name) || $name == '') {
				throw new ManifestException("local type name invalid");
			}
			
			$serializationManifest_afe = $dirname
				. DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name)
				. DIRECTORY_SEPARATOR . $basename;
			
			$manifestParser = new static($type, true, $this->namespace, $serializationManifest_afe, false);
			$manifestParser->interfacer = $this->interfacer;
			$manifestParser->castValues = $this->castValues;
			$manifestParsers[$this->namespace. '\\' . $name] = $manifestParser;
		}
		
		return $manifestParsers;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getCurrentProperties()
	 */
	protected function _getCurrentProperties() {
		return $this->interfacer->hasValue($this->manifest, 'properties', true)
			? $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, 'properties', true))
			: [];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getCurrentPropertyModelName()
	 */
	public function getCurrentPropertyModelName() {
		return $this->_getPropertyModelName($this->_getCurrentPropertyNode());
	}
	
	/**
	 * 
	 * @param mixed $property
	 * @return string
	 */
	protected function _getPropertyModelName($property) {
		$modelName = $this->interfacer->getValue($property, 'type');
		if ($modelName == 'array') {
			$modelName = $this->_getPropertyModelName($this->interfacer->getValue($property, 'values', true));
		}
		return $modelName;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_isCurrentPropertyForeign()
	 */
	protected function _isCurrentPropertyForeign() {
		return $this->_getBooleanValue($this->_getCurrentPropertyNode(), self::IS_FOREIGN, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getBaseInfosProperty()
	 */
	protected function _getBaseInfosProperty(AbstractModel $propertyModel) {
		$currentProperty = $this->_getCurrentPropertyNode();
		
		$isId       = $this->_getBooleanValue($currentProperty, self::IS_ID, false);
		$isPrivate  = $this->_getBooleanValue($currentProperty, self::IS_PRIVATE, false);
		$isNotNull  = $this->_getBooleanValue($currentProperty, self::NOT_NULL, false);
		$isRequired = $this->_getBooleanValue($currentProperty, self::IS_REQUIRED, false);
		$isIsolated = $this->_getBooleanValue($currentProperty, self::IS_ISOLATED, false);
		$name       = $this->interfacer->getValue($currentProperty, self::NAME);
		$model      = $this->_completePropertyModel($currentProperty, $propertyModel);
		
		if ($this->interfacer->hasValue($currentProperty, self::XML_ELEM_TYPE)) {
			$type = $this->interfacer->getValue($currentProperty, self::XML_ELEM_TYPE);
			if ($type === self::XML_ATTRIBUTE) {
				$interfaceAsNodeXml = false;
			} else if ($type === self::XML_NODE) {
				$interfaceAsNodeXml = true;
			} else {
				throw new ManifestException("invalid value '$type' for property '".self::XML_ELEM_TYPE."'");
			}
		} else {
			$interfaceAsNodeXml = null;
		}
		
		return [$name, $model, $isId, $isPrivate, $isNotNull, $isRequired, $isIsolated, $interfaceAsNodeXml];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getRestrictions()
	 */
	protected function _getRestrictions($currentNode, AbstractModel $model) {
		if (!($model instanceof SimpleModel)) {
			return [];
		}
		$restrictions = [];
		
		if ($this->_getBooleanValue($currentNode, self::NOT_EMPTY, false)) {
			$restrictions[] = new NotEmptyString();
		}
		if ($this->interfacer->hasValue($currentNode, self::LENGTH)) {
			$restrictions[] = new Length($this->interfacer->getValue($currentNode, self::LENGTH));
		}
		if ($this->_getBooleanValue($currentNode, self::IS_MODEL_NAME, false)) {
			$restrictions[] = new ModelName();
		}
		if ($this->interfacer->hasValue($currentNode, self::ENUM, true)) {
			$enumValues = $this->interfacer->getTraversableNode($this->interfacer->getValue($currentNode, self::ENUM, true));
			if ($this->interfacer instanceof XMLInterfacer) {
				if ($model instanceof ModelInteger) {
					foreach ($enumValues as $key => $domNode) {
						$enumValues[$key] = (integer) $this->interfacer->extractNodeText($domNode);
					}
				} elseif (($model instanceof ModelString) || ($model instanceof ModelFloat)) {
					foreach ($enumValues as $key => $domNode) {
						$enumValues[$key] = $this->interfacer->extractNodeText($domNode);
					}
				} else {
					throw new ManifestException('enum cannot be defined on '.$model->getName());
				}
			}
			$restrictions[] = new Enum($enumValues);
		}
		if ($this->interfacer->hasValue($currentNode, self::INTERVAL)) {
			$restrictions[] = new Interval($this->interfacer->getValue($currentNode, self::INTERVAL), $model);
		}
		if ($this->interfacer->hasValue($currentNode, self::PATTERN)) {
			if ($this->interfacer->hasValue($currentNode, self::REGEX)) {
				throw new ManifestException(self::PATTERN.' cannot coexist with '.self::REGEX);
			}
			if (!($model instanceof ModelString)) {
				throw new ManifestException('pattern cannot be defined on '.$model->getName());
			}
			$regex = RegexCollection::getInstance()->getRegex($this->interfacer->getValue($currentNode, self::PATTERN));
			$restrictions[] = new Regex($regex);
		} elseif ($this->interfacer->hasValue($currentNode, self::REGEX)) {
			if (!($model instanceof ModelString)) {
				throw new ManifestException('regex cannot be defined on '.$model->getName());
			}
			$restrictions[] = new Regex($this->interfacer->getValue($currentNode, self::REGEX));
		}
		
		return $restrictions;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getDefaultValue()
	 */
	protected function _getDefaultValue(AbstractModel $propertyModel) {
		$currentProperty = $this->_getCurrentPropertyNode();
		
		if ($this->interfacer->hasValue($currentProperty, 'default')) {
			$default = $this->interfacer->getValue($currentProperty, 'default');
			if ($propertyModel instanceof ModelDateTime) {
				if (new \DateTime($default) === false) {
					throw new ManifestException('invalid default value time format : '.$default);
				}
			} else if ($propertyModel instanceof SimpleModel) {
				$default = $propertyModel->importSimple($default, $this->interfacer);
			} else {
				throw new ManifestException('default value can\'t be applied on complex model');
			}
		} else {
			$default = null;
		}
		return $default;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getDependencyProperties()
	 */
	protected function _getDependencyProperties() {
		return $this->_getArrayStringValue($this->_getCurrentPropertyNode(), self::DEPENDS);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getConflicts()
	 */
	public function getConflicts() {
		$conflicts = [];
		if (!$this->interfacer->hasValue($this->manifest, self::CONFLICTS, true)) {
			return $conflicts;
		}
		$values = $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, self::CONFLICTS, true));
		foreach ($values as $propertiesNode) {
			$conflicts[] = $this->interfacer->getTraversableNode($propertiesNode);
		}
		if ($this->interfacer instanceof XMLInterfacer) {
			foreach ($conflicts as $i => $properties) {
				foreach ($properties as $j => $domNode) {
					$conflicts[$i][$j] = $this->interfacer->extractNodeText($domNode);
				}
			}
		}
		
		return $conflicts;
	}
	
	/**
	 * add model container if needed
	 * @param mixed $propertyNode
	 * @param \Comhon\Model\AbstractModel $uniqueModel
	 * @throws \Exception
	 * @return \Comhon\Model\AbstractModel
	 */
	private function _completePropertyModel($propertyNode, AbstractModel $uniqueModel) {
		$propertyModel = $uniqueModel;
	
		if ($this->isArrayNode($propertyNode)) {
			$valuesNode = $this->interfacer->getValue($propertyNode, 'values', true);
			if (is_null($valuesNode)) {
				throw new ManifestException('type array must have a values node');
			}
			$valuesName = $this->interfacer->getValue($valuesNode, 'name');
			if (is_null($valuesName)) {
				throw new ManifestException('type array must have a values name property');
			}
			
			$isAssociative = $this->_getBooleanValue($propertyNode, self::IS_ASSOCIATIVE, false);
			$isNotNullElement = $this->_getBooleanValue($valuesNode, self::NOT_NULL, false);
			$isIsolatedElement = $this->_getBooleanValue($valuesNode, self::IS_ISOLATED, false);
			
			$subModel = $this->_completePropertyModel($valuesNode, $uniqueModel);
			$elementRestrictions = $subModel instanceof ModelArray ? [] : $this->_getRestrictions($valuesNode, $uniqueModel);
			$arrayRestrictions = [];
			
			if ($this->interfacer->hasValue($propertyNode, self::SIZE)) {
				$arrayRestrictions[] = new Size($this->interfacer->getValue($propertyNode, self::SIZE));
			}
			if ($this->_getBooleanValue($propertyNode, self::NOT_EMPTY, false)) {
				$arrayRestrictions[] = new NotEmptyArray();
			}
			$propertyModel = new ModelArray($subModel, $isAssociative, $valuesName, $arrayRestrictions, $elementRestrictions, $isNotNullElement, $isIsolatedElement);
		}
		return $propertyModel;
	}
	
	/**
	 * 
	 * @param mixed $propertyNode
	 * @return boolean
	 */
	protected function isArrayNode($propertyNode) {
		return $this->interfacer->getValue($propertyNode, 'type') == 'array';
	}
	
}