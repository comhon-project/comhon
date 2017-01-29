<?php
namespace comhon\database;

class OnLiteral extends Literal {

	public function __construct($pTableLeft, $pColumnLeft, $pOperator, $pTableRight, $pColumnRight) {
		$this->mFunction = $pFunction;
		parent::__construct($pTableLeft, $pColumnLeft, $pOperator, $pTableRight.'.'.$pColumnRight);
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		return sprintf("%s.%s %s %s", $this->mFunction, $this->mTable, $this->mColumn, $this->mOperator, $this->mValue);
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($pStdObject) {
		throw new \Exception("cannot build OnLiteral from stdClass object");
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @param [] $pLeftJoins
	 * @param [] $pLiteralCollection
	 * @throws \Exception
	 */
	public static function stdObjectToLiteral($pStdObject, &$pLeftJoins, $pLiteralCollection = null) {
		throw new \Exception("cannot build OnLiteral from stdClass object");
	}
	
}