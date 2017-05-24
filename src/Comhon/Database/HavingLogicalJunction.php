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

class HavingLogicalJunction extends LogicalJunction {
	
	/**
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->_addLiteral($literal);
	}
	
	/**
	 * @param HavingLiteral $literal
	 */
	private function _addLiteral(HavingLiteral $literal) {
		$this->literals[] = $literal;
	}
	
	/**
	 * @param LogicalJunction $logicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $logicalJunction) {
		$this->_addLogicalJunction($logicalJunction);
	}
	
	/**
	 * @param HavingLogicalJunction $logicalJunction
	 */
	private function _addLogicalJunction(HavingLogicalJunction $logicalJunction) {
		$this->logicalJunction[] = $logicalJunction;
	}
	
	
	public static function stdObjectToHavingLogicalJunction($stdObject, $firstTable, $lastTable, $lastModel, $allowPrivateProperties) {
		if (!is_object($stdObject) || !isset($stdObject->type) || (isset($stdObject->logicalJunctions) && !is_array($stdObject->logicalJunctions)) || (isset($stdObject->literals) && !is_array($stdObject->literals))) {
			throw new \Exception('malformed stdObject LogicalJunction : '.json_encode($stdObject));
		}
		$logicalJunction = new HavingLogicalJunction($stdObject->type);
		if (isset($stdObject->logicalJunctions)) {
			foreach ($stdObject->logicalJunctions as $stdObjectLogicalJunction) {
				$logicalJunction->addLogicalJunction(self::stdObjectToHavingLogicalJunction($stdObjectLogicalJunction, $firstTable, $lastTable, $lastModel, $allowPrivateProperties));
			}
		}
		if (isset($stdObject->literals)) {
			foreach ($stdObject->literals as $stdObjectLiteral) {
				$table = isset($stdObjectLiteral->havingLiteral->function) && ($stdObject->havingLiteral->function == HavingLiteral::COUNT) ? $firstTable : $lastTable;
				$logicalJunction->addLiteral(HavingLiteral::stdObjectToHavingLiteral($stdObjectLiteral, $table, $lastModel, $allowPrivateProperties));
			}
		}
		return $logicalJunction;
	}
	
}