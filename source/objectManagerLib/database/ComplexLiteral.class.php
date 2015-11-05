<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\database\DatabaseController;

/**
 * a complex literal is like a literal but it value is a query
 * exemple : 
 * query => SELECT * FROM table WHERE column IN (SELECT column FROM table_2 WHERE column_2 = "a_value") AND column_3 = "a_value_2"
 * complex literal => column IN (SELECT column FROM table WHERE column_2 = "a_value")
 */
class ComplexLiteral extends WhereLiteral {

	const IN     = "IN";
	const NOT_IN = "NOT IN";
	
	protected static $sAcceptedOperators = array(
			self::IN     => null,
			self::NOT_IN => null
	);
	
	protected static $sOppositeOperator = array(
			self::IN     => self::NOT_IN,
			self::NOT_IN => self::IN
	);
	
	public function __construct($pTable, $pColumn, $pOperator, $pValue, $pModelName = null) {
		if (is_null($pColumn) && !is_null($pModelName)) {
			$lModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
			if (count($lModel->getIds()) != 1) {
				throw new \Exception("error : complex literal with model must have one and only one property id");
			}
			$pColumn = $lModel->getProperty($lModel->getFirstId())->getSerializationName();
		}
		parent::__construct($pTable, $pColumn, $pOperator, $pValue, $pModelName);
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedOperators)) {
			throw new \Exception("operator '".$this->mOperator."' doesn't exists");
		}
		if (!is_null($this->mValue) && !($this->mValue instanceof SelectQuery)) {
			throw new \Exception("complex literal must have a query value");
		}
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		list($lQuery, $lValues) = $this->mValue->export();
		foreach ($lValues as $lValue) {
			$pValues[] = $lValue;
		}
		return sprintf("%s.%s %s (%s)", $this->mTable, $this->mColumn, $this->mOperator, $lQuery);
	}
	
	/**
	 * can't be used to populate a database query
	 * @return string
	 */
	public function exportWithValue() {
		return sprintf("%s.%s %s (%s)", $this->mTable, $this->mColumn, $this->mOperator, $this->mValue->exportWithValue());
	}
}