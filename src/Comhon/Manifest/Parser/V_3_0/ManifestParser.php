<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Manifest\Parser\V_3_0;

use Comhon\Manifest\Parser\V_2_0\ManifestParser as ParentManifestParser;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Manifest\ManifestException;
use Comhon\Interfacer\Interfacer;

class ManifestParser extends ParentManifestParser {
	
	/** @var string */
	const OBJECT_CLASS         = 'object_class';
	
	const PROPERTY_TO_SIMPLE_MODEL = [
		'Comhon\Manifest\Property\String' => 'string',
		'Comhon\Manifest\Property\Integer' => 'integer',
		'Comhon\Manifest\Property\Index' => 'index',
		'Comhon\Manifest\Property\Float' => 'float',
		'Comhon\Manifest\Property\Percentage' => 'percentage',
		'Comhon\Manifest\Property\Boolean' => 'boolean',
		'Comhon\Manifest\Property\DateTime' => 'dateTime'
	];
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::isAbstract()
	 */
	public function isAbstract() {
		return $this->_getBooleanValue($this->manifest, self::IS_ABSTRACT, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::isSharedParentId()
	 */
	public function isSharedParentId() {
		return $this->_getBooleanValue($this->manifest, self::SHARE_PARENT_ID, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\V_2_0\ManifestParser::sharedId()
	 */
	public function sharedId() {
		return $this->interfacer->getValue($this->manifest, self::SHARED_ID);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Manifest\Parser\ManifestParser::getExtends()
	 */
	public function getExtends() {
		if ($this->interfacer->hasValue($this->manifest, self::_EXTENDS, true)) {
			$extends = $this->interfacer->getTraversableNode($this->interfacer->getValue($this->manifest, self::_EXTENDS, true));
			if ($this->interfacer instanceof XMLInterfacer) {
				foreach ($extends as $key => $domNode) {
					$extends[$key] = $this->interfacer->extractNodeText($domNode);
				}
			}
		} else {
			$extends = null;
		}
		
		return $extends;
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
		$inheritance = $this->interfacer->getValue($property, Interfacer::INHERITANCE_KEY);
		if (array_key_exists($inheritance, self::PROPERTY_TO_SIMPLE_MODEL)) {
			$modelName = self::PROPERTY_TO_SIMPLE_MODEL[$inheritance];
		} elseif ($inheritance == 'Comhon\Manifest\Property\Object') {
			$modelName = $this->interfacer->getValue($property, 'model');
		} elseif ($inheritance == 'Comhon\Manifest\Property\Array\Scalar') {
			$modelName = $this->_getPropertyModelName($this->interfacer->getValue($property, 'values', true));
		} elseif ($inheritance == 'Comhon\Manifest\Property\Array\Object') {
			$modelName = $this->_getPropertyModelNameObject($this->interfacer->getValue($property, 'values', true));
		}else {
			throw new ManifestException('invalid '.Interfacer::INHERITANCE_KEY.' value : '.$inheritance);
		}
		return $modelName;
	}
	
	/**
	 *
	 * @param mixed $property
	 * @return string
	 */
	protected function _getPropertyModelNameObject($property) {
		if ($this->interfacer->hasValue($property, Interfacer::INHERITANCE_KEY)) {
			$inheritance = $this->interfacer->getValue($property, Interfacer::INHERITANCE_KEY);
			if ($inheritance == 'Comhon\Manifest\Property\Object') {
				$modelName = $this->interfacer->getValue($property, 'model');
			} elseif ($inheritance == 'Comhon\Manifest\Property\Array\Scalar' || $inheritance == 'Comhon\Manifest\Property\Array\Object') {
				$modelName = $this->_getPropertyModelNameObject($this->interfacer->getValue($property, 'values', true));
			} else {
				throw new ManifestException('invalid '.Interfacer::INHERITANCE_KEY.' value : '.$inheritance);
			}
		} else {
			$modelName = $this->interfacer->getValue($property, 'model');
		}
		return $modelName;
	}
	
	/**
	 *
	 * @param mixed $propertyNode
	 * @return boolean
	 */
	protected function isArrayProperty($propertyNode) {
		$inheritance = $this->interfacer->getValue($propertyNode, Interfacer::INHERITANCE_KEY);
		return $inheritance == 'Comhon\Manifest\Property\Array\Scalar' || $inheritance == 'Comhon\Manifest\Property\Array\Object';
	}
}
