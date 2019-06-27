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
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelRestrictedArray;
use Comhon\Manifest\Parser\ManifestParser as ParentManifestParser;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Model\AbstractModel;

class ManifestParser extends ParentManifestParser {

	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getExtends()
	 */
	public function getExtends() {
		$currentNode = $this->focusLocalTypes ? current($this->localTypes) : $this->manifest;
		return $this->interfacer->getValue($currentNode, self::_EXTENDS);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getInheritanceRequestable()
	 */
	public function getInheritanceRequestable() {
		$inheritanceRequestables = null;
		
		if ($this->interfacer->hasValue($this->manifest, self::INHERITANCE_REQUESTABLES, true)) {
			$node = $this->interfacer->getValue($this->manifest, self::INHERITANCE_REQUESTABLES, true);
			$inheritanceRequestables = $this->interfacer->getTraversableNode($node);
			if ($this->interfacer instanceof XMLInterfacer) {
				foreach ($inheritanceRequestables as $key => $domNode) {
					$inheritanceRequestables[$key] = $this->interfacer->extractNodeText($domNode);
				}
			}
		}
		
		return $inheritanceRequestables;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::isMain()
	 */
	public function isMain() {
		$isMain = $this->interfacer->hasValue($this->manifest, self::IS_MAIN)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($this->manifest, self::IS_MAIN))
					: $this->interfacer->getValue($this->manifest, self::IS_MAIN)
			)
			: false;
		return $isMain;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::isSerializable()
	 */
	public function isSerializable() {
		return $this->interfacer->hasValue($this->manifest, self::IS_SERIALIZABLE)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($this->manifest, self::IS_SERIALIZABLE))
					: $this->interfacer->getValue($this->manifest, self::IS_SERIALIZABLE)
			)
			: false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getObjectClass()
	 */
	public function getObjectClass() {
		if ($this->focusLocalTypes) {
			$current = current($this->localTypes);
			return $this->interfacer->getValue($current, self::_OBJECT);
		} else {
			return $this->interfacer->getValue($this->manifest, self::_OBJECT);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getCurrentLocalModelName()
	 */
	public function getCurrentLocalModelName() {
		$current = current($this->localTypes);
		return $this->interfacer->getValue($current, self::NAME);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getLocalTypes()
	 */
	protected function _getLocalTypes() {
		return $this->interfacer->hasValue($this->manifest, 'types', true)
			? $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, 'types', true))
			: []; 
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getCurrentProperties()
	 */
	protected function _getCurrentProperties() {
		$parentNode = $this->focusLocalTypes ? current($this->localTypes) : $this->manifest;
		return $this->interfacer->hasValue($parentNode, 'properties', true)
			? $this->interfacer->getTraversableNode($this->interfacer->getValue($parentNode, 'properties', true))
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
	private function _getPropertyModelName($property) {
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
		$currentProperty = $this->_getCurrentPropertyNode();
		
		return $this->interfacer->hasValue($currentProperty, self::IS_FOREIGN)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($currentProperty, self::IS_FOREIGN))
					: $this->interfacer->getValue($currentProperty, self::IS_FOREIGN)
			)
			: false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getBaseInfosProperty()
	 */
	protected function _getBaseInfosProperty(AbstractModel $propertyModel) {
		$currentProperty = $this->_getCurrentPropertyNode();
	
		$isId = $this->interfacer->hasValue($currentProperty, self::IS_ID)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($currentProperty, self::IS_ID))
					: $this->interfacer->getValue($currentProperty, self::IS_ID)
			)
			: false;
		
		$isPrivate = $this->interfacer->hasValue($currentProperty, self::IS_PRIVATE)
			? (
				$this->castValues
					? $this->interfacer->castValueToBoolean($this->interfacer->getValue($currentProperty, self::IS_PRIVATE))
					: $this->interfacer->getValue($currentProperty, self::IS_PRIVATE)
			)
			: false;
		
		$name      = $this->interfacer->getValue($currentProperty, self::NAME);
		$model     = $this->_completePropertyModel($currentProperty, $propertyModel);
		
		if ($this->interfacer->hasValue($currentProperty, 'xml')) {
			$type = $this->interfacer->getValue($currentProperty, 'xml');
			if ($type === self::XML_ATTRIBUTE) {
				$interfaceAsNodeXml = false;
			} else if ($type === self::XML_NODE) {
				$interfaceAsNodeXml = true;
			} else {
				throw new ManifestException("invalid value '$type' for property 'xml'");
			}
		} else {
			$interfaceAsNodeXml = null;
		}
		
		return [$name, $model, $isId, $isPrivate, $interfaceAsNodeXml];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::_getRestriction()
	 */
	protected function _getRestriction($currentNode, AbstractModel $uniqueModel) {
		if ($uniqueModel instanceof ModelContainer) {
			return null;
		}
		$restriction  = null;
		
		if ($this->interfacer->hasValue($currentNode, 'enum', true)) {
			$enumValues = $this->interfacer->getTraversableNode($this->interfacer->getValue($currentNode, 'enum', true));
			if ($this->interfacer instanceof XMLInterfacer) {
				if ($uniqueModel instanceof ModelInteger) {
					foreach ($enumValues as $key => $domNode) {
						$enumValues[$key] = (integer) $this->interfacer->extractNodeText($domNode);
					}
				} elseif (($uniqueModel instanceof ModelString) || ($uniqueModel instanceof ModelFloat)) {
					foreach ($enumValues as $key => $domNode) {
						$enumValues[$key] = $this->interfacer->extractNodeText($domNode);
					}
				} else {
					throw new ManifestException('enum cannot be defined on '.$uniqueModel->getName());
				}
			}
			$restriction = new Enum($enumValues);
		}
		elseif ($this->interfacer->hasValue($currentNode, 'interval')) {
			$restriction = new Interval($this->interfacer->getValue($currentNode, 'interval'), $uniqueModel);
			
		}
		elseif ($this->interfacer->hasValue($currentNode, 'pattern')) {
			if (!($uniqueModel instanceof ModelString)) {
				throw new ManifestException('pattern cannot be defined on '.$uniqueModel->getName());
			}
			$restriction = new Regex($this->interfacer->getValue($currentNode, 'pattern'));
		}
		
		return $restriction;
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
	 * add model container if needed
	 * @param mixed $propertyNode
	 * @param \Comhon\Model\AbstractModel $uniqueModel
	 * @throws \Exception
	 * @return \Comhon\Model\AbstractModel
	 */
	private function _completePropertyModel($propertyNode, AbstractModel $uniqueModel) {
		$propertyModel = $uniqueModel;
		$typeId        = $this->interfacer->getValue($propertyNode,'type');
	
		if ($typeId == 'array') {
			$valuesNode = $this->interfacer->getValue($propertyNode, 'values', true);
			if (is_null($valuesNode)) {
				throw new ManifestException('type array must have a values node');
			}
			$valuesName = $this->interfacer->getValue($valuesNode, 'name');
			if (is_null($valuesName)) {
				throw new ManifestException('type array must have a values name property');
			}
			
			$isAssociative = $this->interfacer->hasValue($propertyNode, self::IS_ASSOCIATIVE)
				? (
					$this->castValues
						? $this->interfacer->castValueToBoolean($this->interfacer->getValue($propertyNode, self::IS_ASSOCIATIVE))
						: $this->interfacer->getValue($propertyNode, self::IS_ASSOCIATIVE)
				)
				: false;
			
			$subModel = $this->_completePropertyModel($valuesNode, $uniqueModel);
			$restriction = $this->_getRestriction($valuesNode, $uniqueModel);
			if (is_null($restriction)) {
				$propertyModel = new ModelArray($subModel, $isAssociative, $valuesName);
			} else {
				$propertyModel = new ModelRestrictedArray($subModel, $restriction, $isAssociative, $valuesName);
			}
		}
		return $propertyModel;
	}
	
}