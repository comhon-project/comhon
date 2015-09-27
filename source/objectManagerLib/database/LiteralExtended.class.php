<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;

class LiteralExtended extends Literal {

	protected $mModel;
	
	/**
	 * constructor
	 * @param unknown $pModelName model linked to your literal. MUST have a database serialization
	 * @param unknown $pPropertyName
	 * @param unknown $pOperator
	 * @param unknown $pValue
	 */
	public function __construct($pModelName, $pPropertyName, $pOperator, $pValue) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
		$lProperty = $this->mModel->getProperty($pPropertyName);
		if (is_null($lSqlTable = $this->mModel->getSqlTableUnit())) {
			throw new \Exception("must have a database serialization");
		}if (is_null($lProperty)) {
			throw new \Exception("'$pModelName' doesn't have property '$pPropertyName'");
		}
		parent::__construct($lSqlTable->getValue("name"), $lProperty->getSerializationName(), $pOperator, $pValue);
	}

	public function getModel() {
		return $this->mModel;
	}
	
}