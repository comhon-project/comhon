<?php
namespace objectManagerLib\database;

class HavingLogicalJunction extends LogicalJunction {
	
	/**
	 * @param Literal $pLiteral
	 */
	public function addLiteral(Literal $pLiteral) {
		$this->_addLiteral($pLiteral);
	}
	
	/**
	 * @param HavingLiteral $pLiteral
	 */
	private function _addLiteral(HavingLiteral $pLiteral) {
		$this->mLiterals[] = $pLiteral;
	}
	
	/**
	 * @param LogicalJunction $pLogicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $pLogicalJunction) {
		$this->_addLogicalJunction($pLogicalJunction);
	}
	
	/**
	 * @param HavingLogicalJunction $pLogicalJunction
	 */
	private function _addLogicalJunction(HavingLogicalJunction $pLogicalJunction) {
		$this->mLogicalJunction[] = $pLogicalJunction;
	}
	
}