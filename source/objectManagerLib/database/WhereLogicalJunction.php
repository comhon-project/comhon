<?php
namespace objectManagerLib\database;

class WhereLogicalJunction extends LogicalJunction {
	
	/**
	 * @param Literal $pLiteral
	 */
	public function addLiteral(Literal $pLiteral) {
		$this->_addLiteral($pLiteral);
	}
	
	/**
	 * @param WhereLiteral $pLiteral
	 */
	private function _addLiteral(WhereLiteral $pLiteral) {
		$this->mLiterals[] = $pLiteral;
	}
	
	/**
	 * @param LogicalJunction $pLogicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $pLogicalJunction) {
		$this->_addLogicalJunction($pLogicalJunction);
	}
	
	/**
	 * @param WhereLogicalJunction $pLogicalJunction
	 */
	private function _addLogicalJunction(WhereLogicalJunction $pLogicalJunction) {
		$this->mLogicalJunction[] = $pLogicalJunction;
	}
	
}