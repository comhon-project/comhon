<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Property;

use Comhon\Object\ObjectArray;
use Comhon\Object\ComhonObject;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelDateTime;
use Comhon\Object\ComhonDateTime;
use Comhon\Model\Model;

class Property {

	protected $mModel;
	protected $mName;
	protected $mSerializationName;
	protected $mIsId;
	protected $mIsPrivate;
	protected $mIsSerializable;
	protected $mDefault;
	protected $mInterfaceAsNodeXml;
	
	/**
	 * 
	 * @param Model $pModel
	 * @param string $pName
	 * @param string $pSerializationName
	 * @param boolean $pIsId
	 * @param boolean $pIsPrivate
	 * @param boolean $pIsSerializable
	 * @param mixed $pDefault
	 * @param unknown $pRestriction
	 * @param boolean $pIsInterfacedAsNodeXml
	 * @throws \Exception
	 */
	public function __construct(Model $pModel, $pName, $pSerializationName = null, $pIsId = false, $pIsPrivate = false, $pIsSerializable = true, $pDefault = null, $pIsInterfacedAsNodeXml = null) {
		$this->mModel = $pModel;
		$this->mName = $pName;
		$this->mSerializationName = is_null($pSerializationName) ? $this->mName : $pSerializationName;
		$this->mIsId = $pIsId;
		$this->mIsPrivate = $pIsPrivate;
		$this->mIsSerializable = $pIsSerializable;
		$this->mDefault = $pDefault;
		
		if ($this->mModel instanceof SimpleModel) {
			$this->mInterfaceAsNodeXml = is_null($pIsInterfacedAsNodeXml) ? false : $pIsInterfacedAsNodeXml;
		} else {
			if (!is_null($pIsInterfacedAsNodeXml) && !$pIsInterfacedAsNodeXml) {
				trigger_error('warning! 8th parameter is ignored, property with complex model is inevitably interfaced as node xml');
			}
			// without inheritance foreign property may be exported as attribute because only id is exported
			// but due to inheritance, model name can be exported with id so we need to export as node
			$this->mInterfaceAsNodeXml = true;
		}
		
		if ($this->mIsId && !($this->mModel instanceof SimpleModel)) {
			throw new \Exception("id property with name '$pName' must be a simple model");
		}
	}
	
	/**
	 * @return Model
	 */
	public function getModel() {
		$this->mModel->load();
		return $this->mModel;
	}
	
	public function getUniqueModel() {
		$lUniqueModel = $this->getModel();
		while ($lUniqueModel instanceof ModelContainer) {
			$lUniqueModel = $lUniqueModel->getModel();
		}
		$lUniqueModel->load();
		return $lUniqueModel;
	}
	
	public function getName() {
		return $this->mName;
	}
	
	public function getSerializationName() {
		return $this->mSerializationName;
	}
	
	public function isId() {
		return $this->mIsId;
	}
	
	public function isPrivate() {
		return $this->mIsPrivate;
	}
	
	public function isSerializable() {
		return $this->mIsSerializable;
	}
	
	public function hasDefaultValue() {
		return !is_null($this->mDefault);
	}
	
	public function getDefaultValue() {
		if ($this->mModel instanceof ModelDateTime) {
			return new ComhonDateTime($this->mDefault);
		}
		return $this->mDefault;
	}
	
	public function getSerialization() {
		return null;
	}
	
	public function isAggregation() {
		return false;
	}
	
	public function isForeign() {
		return false;
	}
	
	public function isComplex() {
		return $this->mModel->isComplex();
	}
	
	public function hasModelDateTime() {
		return ($this->mModel instanceof ModelDateTime);
	}
	
	/**
	 * verifiy if property has several serialization names
	 * @return boolean
	 */
	public function hasMultipleSerializationNames() {
		return false;
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * 
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($pPrivate, $pSerialization) {
		return ($pPrivate || !$this->mIsPrivate) && (!$pSerialization || $this->mIsSerializable);
	}
	
	/**
	 * verify if property is exportable in public/private/serialization mode
	 * 
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @param mixed $pValue value that we want to export
	 * @return boolean true if property is interfaceable
	 */
	public function isExportable($pPrivate, $pSerialization, $pValue) {
		return (is_null($pValue) || $this->getModel()->verifValue($pValue)) && $this->isInterfaceable($pPrivate, $pSerialization);
	}
	
	/**
	 * verify if value is satisfiable regarding restriction property
	 *
	 * @param mixed $pValue
	 * @param boolean $pThrowException
	 * @return boolean true if property is satisfiable
	 */
	public function isSatisfiable($pValue, $pThrowException = false) {
		return true;
	}
	
	/**
	 * verify if property is exported/imported as node for xml export/import
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfacedAsNodeXml() {
		return $this->mInterfaceAsNodeXml;
	}
	
	public function getAggregationProperties() {
		return null;
	}
	
	/**
	 * 
	 * @param ComhonObject $pObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValue(ComhonObject $pObject, $pPropertiesFilter = [], $pForceLoad = false) {
		throw new \Exception('cannot load object, property is not foreign property');
	}
	
	/**
	 * 
	 * @param ObjectArray $pObject
	 * @param ComhonObject $pParentObject
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @throws \Exception
	 */
	public function loadValueIds(ObjectArray $pObject, ComhonObject $pParentObject, $pForceLoad = false) {
		throw new \Exception('cannot load aggregation ids, property is not aggregation property');
	}
	
	/**
	 * 
	 * @param Property $pProperty
	 * @return boolean
	 */
	public function isEqual(Property $pProperty) {
		return $this === $pProperty || (
			get_class($this)          === get_class($pProperty) &&
			$this->mModel             === $pProperty->getModel() &&
			$this->mName              === $pProperty->getName() &&
			$this->mIsId              === $pProperty->isId() &&
			$this->mIsPrivate         === $pProperty->isPrivate() &&
			$this->mDefault           === $pProperty->getDefaultValue() &&
			$this->mIsSerializable    === $pProperty->isSerializable() &&
			$this->mSerializationName === $pProperty->getSerializationName()
		);
	}
}