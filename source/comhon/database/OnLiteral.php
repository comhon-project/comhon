<?php
namespace comhon\database;

class OnLiteral extends Literal {

	private $mColumnRight;
	
	public function __construct($pTableLeft, $pColumnLeft, $pOperator, $pTableRight, $pColumnRight) {
		$this->mColumnRight = $pColumnRight;
		parent::__construct($pTableLeft, $pColumnLeft, $pOperator, $pTableRight);
	}
	
	/**
	 * @return string
	 */
	public function getColumnRight() {
		return $this->mColumnRight;
	}
	
	/**
	 * 
	 * @return string|TableNode
	 */
	public function getTableRight() {
		return $this->mValue;
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		$lLeft = (($this->mTable instanceof TableNode) ? $this->mTable->getExportName() : $this->mTable) . '.' . $this->mColumn;
		$lRight = (($this->mValue instanceof TableNode) ? $this->mValue->getExportName() : $this->mValue) . '.' . $this->mColumnRight;
		return sprintf("%s %s %s", $lLeft, $this->mOperator, $lRight);
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