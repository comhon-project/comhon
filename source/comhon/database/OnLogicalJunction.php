<?php
namespace comhon\database;

class OnLogicalJunction extends LogicalJunction {
	
	/**
	 * @param Literal $pLiteral
	 */
	public function addLiteral(Literal $pLiteral) {
		$this->_addLiteral($pLiteral);
	}
	
	/**
	 * @param OnLiteral $pLiteral
	 */
	private function _addLiteral(OnLiteral $pLiteral) {
		$this->mLiterals[] = $pLiteral;
	}
	
	/**
	 * @param LogicalJunction $pLogicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $pLogicalJunction) {
		$this->_addLogicalJunction($pLogicalJunction);
	}
	
	/**
	 * @param OnLogicalJunction $pLogicalJunction
	 */
	private function _addLogicalJunction(OnLogicalJunction $pLogicalJunction) {
		$this->mLogicalJunction[] = $pLogicalJunction;
	}
	
}