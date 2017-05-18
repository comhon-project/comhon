<?php
namespace comhon\model\restriction;

use comhon\model\Model;

interface Restriction {
	
	/**
	 *
	 * @param mixed $pValue
	 */
	public function satisfy($pValue);
	
	/**
	 * verify if specified restriction is equal to $this
	 * @param Restriction $pRestriction
	 */
	public function isEqual(Restriction $pRestriction);
	
	/**
	 * verify if specified model can use this restriction
	 * @param Model $pModel
	 */
	public function isAllowedModel(Model $pModel);
	
	/**
	 * stringify restriction and value
	 * @param mixed $pValue
	 */
	public function toString($pValue);
	
}