<?php

use GenLib\objectManager\singleton\InstanceModel;

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
		$this->mTable = $lSqlTable->getValue("name");
		$this->mPropertyName = $pPropertyName;
		$this->mOperator = $pOperator;
		$this->mValue = $pValue;
	}

	public function getModel() {
		return $this->mModel;
	}
	
}