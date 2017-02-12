<?php
namespace comhon\database;

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
	
	
	public static function stdObjectToHavingLogicalJunction($pStdObject, $pFirstTable, $pLastTable, $pLastModel) {
		if (!is_object($pStdObject) || !isset($pStdObject->type) || (isset($pStdObject->logicalJunctions) && !is_array($pStdObject->logicalJunctions)) || (isset($pStdObject->literals) && !is_array($pStdObject->literals))) {
			throw new \Exception('malformed stdObject LogicalJunction : '.json_encode($pStdObject));
		}
		$lLogicalJunction = new HavingLogicalJunction($pStdObject->type);
		if (isset($pStdObject->logicalJunctions)) {
			foreach ($pStdObject->logicalJunctions as $lStdObjectLogicalJunction) {
				$lLogicalJunction->addLogicalJunction(self::stdObjectToHavingLogicalJunction($lStdObjectLogicalJunction, $pFirstTable, $pLastTable, $pLastModel));
			}
		}
		if (isset($pStdObject->literals)) {
			foreach ($pStdObject->literals as $lStdObjectLiteral) {
				$lTable = isset($lStdObjectLiteral->havingLiteral->function) && ($pStdObject->havingLiteral->function == HavingLiteral::COUNT) ? $pFirstTable : $pLastTable;
				$lLogicalJunction->addLiteral(HavingLiteral::stdObjectToHavingLiteral($lStdObjectLiteral, $lTable, $pLastModel));
			}
		}
		return $lLogicalJunction;
	}
	
}