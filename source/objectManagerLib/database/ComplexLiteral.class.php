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
class ComplexLiteral extends Literal {

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
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedOperators)) {
			throw new \Exception("operator '".$this->mOperator."' doesn't exists");
		}
		if (!is_null($this->mValue) && !is_string($this->mValue)) {
			throw new \Exception("complex literal must have a query value");
		}
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		//list($lQuery, $lValues) = DatabaseController::selectToString($this->mJoinedTables, array($this->mJoinedTables->getFirstTable() => array($this->mFirstTableId)), $this->mJoinedTables->getFirstTable().".".$this->mFirstTableId, $pHavingCount);
		return sprintf("%s.%s %s (%s)", $this->mTable, $this->mPropertyName, $this->mOperator, $this->mValue);
	}
}