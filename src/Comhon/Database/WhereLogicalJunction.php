<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

class WhereLogicalJunction extends LogicalJunction {
	
	/**
	 * add literal
	 * 
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->_addLiteral($literal);
	}
	
	/**
	 * add where literal
	 * 
	 * @param WhereLiteral $literal
	 */
	private function _addLiteral(WhereLiteral $literal) {
		$this->literals[] = $literal;
	}
	
	/**
	 * add logical junction
	 * 
	 * @param LogicalJunction $logicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $logicalJunction) {
		$this->_addLogicalJunction($logicalJunction);
	}
	
	/**
	 * add where logical junction
	 * 
	 * @param WhereLogicalJunction $logicalJunction
	 */
	private function _addLogicalJunction(WhereLogicalJunction $logicalJunction) {
		$this->logicalJunction[] = $logicalJunction;
	}
	
}