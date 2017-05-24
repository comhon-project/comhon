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
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->_addLiteral($literal);
	}
	
	/**
	 * @param OnLiteral $literal
	 */
	private function _addLiteral(OnLiteral $literal) {
		$this->literals[] = $literal;
	}
	
	/**
	 * @param LogicalJunction $logicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $logicalJunction) {
		$this->_addLogicalJunction($logicalJunction);
	}
	
	/**
	 * @param OnLogicalJunction $logicalJunction
	 */
	private function _addLogicalJunction(OnLogicalJunction $logicalJunction) {
		$this->logicalJunction[] = $logicalJunction;
	}
	
}