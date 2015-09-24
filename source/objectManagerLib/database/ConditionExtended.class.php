<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;

class ConditionExtended extends Condition {

	protected $mModel;
	
	/**
	 * constructor
	 * @param unknown $pModelName model linked to your condition. MUST have a database serialization
	 * @param unknown $pPropertyName
	 * @param unknown $pOperator
	 * @param unknown $pValue
	 */
	public function __construct($pModelName, $pPropertyName, $pOperator, $pValue) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
		if (is_null($lSqlTable = $this->mModel->getSqlTableUnit())) {
			throw new \Exception("must have a database serialization");
		}
		parent::__construct($lSqlTable->getValue("name"), $pPropertyName, $pOperator, $pValue);
	}

	public function getModel() {
		return $this->mModel;
	}
	
}