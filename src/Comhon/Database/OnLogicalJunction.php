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

class OnLogicalJunction extends LogicalJunction {
	
	/**
	 * add literal
	 * 
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->_addLiteral($literal);
	}
	
	/**
	 * add on literal
	 * 
	 * @param OnLiteral $literal
	 */
	private function _addLiteral(OnLiteral $literal) {
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
	 * add on logical junction
	 * 
	 * @param OnLogicalJunction $logicalJunction
	 */
	private function _addLogicalJunction(OnLogicalJunction $logicalJunction) {
		$this->logicalJunction[] = $logicalJunction;
	}
	
}