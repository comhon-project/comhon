<?php
namespace comhon\database;

class NotNullJoinLiteral extends Literal {

	private $mConjunction;
	
	public function __construct() {
		$this->mConjunction = new Conjunction();
	}
	
	public function addLiteral($pTable, $pColumn) {
		$this->mConjunction->addLiteral(new Literal($pTable, $pColumn, Literal::DIFF, null));
	}
	
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		return $this->mConjunction->export($pValues);
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($pStdObject) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @param [] $pLeftJoins
	 * @param [] $pLiteralCollection
	 * @throws \Exception
	 */
	public static function stdObjectToLiteral($pStdObject, &$pLeftJoins, $pLiteralCollection = null) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
}