<?php
namespace comhon\model\property;

use comhon\model\restriction\Restriction;
use comhon\model\Model;
use comhon\exception\NotSatisfiedRestrictionException;

class RestrictedProperty extends Property {
	
	private $mRestriction;
	
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
	 * @param Restriction $pRestriction
	 * @throws \Exception
	 */
	public function __construct(Model $pModel, $pName, Restriction $pRestriction, $pSerializationName = null, $pIsId = false, $pIsPrivate = false, $pIsSerializable = true, $pDefault = null, $pIsInterfacedAsNodeXml = null) {
		parent::__construct($pModel, $pName, $pSerializationName, false, $pIsPrivate, $pIsSerializable);
		if (!$pRestriction->isAllowedModel($this->mModel)) {
			throw new \Exception('restriction doesn\'t allow specified model');
		}
		$this->mRestriction = $pRestriction;
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
		$this->isSatisfiable($pValue, true);
		return parent::isExportable($pPrivate, $pSerialization, $pValue);
	}
	
	/**
	 * verify if value is satisfiable regarding restriction property
	 *
	 * @param mixed $pValue
	 * @param boolean $pThrowException
	 * @return boolean true if property is satisfiable
	 */
	public function isSatisfiable($pValue, $pThrowException = false) {
		$lIsSatisfiable = $this->mRestriction->satisfy($pValue);
		if (!$lIsSatisfiable && $pThrowException) {
			throw new NotSatisfiedRestrictionException($pValue, $this->mRestriction);
		}
		return $lIsSatisfiable;
	}
	
}