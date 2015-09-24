<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;

class ConditionExtended extends Condition {

	/**
	 * constructor
	 * @param string $pModelName model linked to your condition. MUST have a database serialization
	 * @param string $pPropertyName
	 * @param string $pOperator
	 * @param integer $pValue
	 */
	public function __construct($pTable, $pPropertyName, $pOperator, $pValue) {
		parent::__construct($lSqlTable->getValue("name"), $pPropertyName, $pOperator, $pValue);
	}

	
}