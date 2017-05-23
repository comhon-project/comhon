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