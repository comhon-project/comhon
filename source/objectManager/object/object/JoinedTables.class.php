<?php
namespace GenLib\objectManager\object\object;

class JoinedTables {

	const INNER_JOIN = "inner join";
	const LEFT_JOIN  = "left join";
	const RIGHT_JOIN = "right join";
	const FULL_JOIN  = "full join";
	
	private static $sAccpetedJoins = array(
		self::INNER_JOIN => null,
		self::LEFT_JOIN  => null,
		self::RIGHT_JOIN => null,
		self::FULL_JOIN  => null,
	);

	private $mFirstTable;
	private $mJoinedTables = array();
	private $mTableNames = array();
	
	/**
	 * 
	 * @param unknown $pTable
	 * @param unknown $pAlias
	 */
	public function __construct($pTable, $pAlias = null) {
		$this->mFirstTable = is_null($pAlias) ? array($pTable) : array($pTable, $pAlias);
		$this->mTableNames[$this->mFirstTable[count($this->mFirstTable) - 1]] = null;
	}
	
	/**
	 * 
	 * @param unknown $pTable
	 * @param unknown $pAlias if you don't want alias, put null value
	 * @param unknown $pJoinType must be in array self::$sAccpetedJoins
	 * @param unknown $pColumn
	 * @param unknown $pForeignColumn must reference a column of a table already added
	 * @param unknown $pForeignTable must reference a table name or an alias already added
	 */
	public function addTable($pTable, $pAlias, $pJoinType, $pColumn, $pForeignColumn, $pForeignTable) {
		if (!array_key_exists($pForeignTable, $this->mTableNames)) {
			throw new \Exception("foreign table is not already added");
		}
		if (!array_key_exists($pJoinType, self::$sAccpetedJoins)) {
			throw new \Exception("undefined join type");
		}
		$lTable = is_null($pAlias) ? array($pTable) : array($pTable, $pAlias);
		$this->mTableNames[$lTable[count($lTable) - 1]] = null;
		$this->mJoinedTables[] = array(
				$pJoinType,
				$lTable,
				array($lTable[count($lTable) - 1], $pColumn),
				array($pForeignTable, $pForeignColumn)
		);
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export() {
		$lJoinedTables = " ".implode(" as ", $this->mFirstTable);
		foreach ($this->mJoinedTables as $lJoinedTable) {
			$lJoinedTables .= " ".$lJoinedTable[0];
			$lJoinedTables .= " ".implode(" as ", $lJoinedTable[1]);
			if (is_array($lJoinedTable[2][1])) {
				$lOnConditions = array();
				foreach ($lJoinedTable[2][1] as $lRightColumn) {
					$lOnConditions[] = $lJoinedTable[2][0].".".$lRightColumn."=".implode(".", $lJoinedTable[3]);
				}
				$lJoinedTables .= " on ".implode(" or ", $lOnConditions);
			} else {
				$lJoinedTables .= " on ".implode(".", $lJoinedTable[2])."=".implode(".", $lJoinedTable[3]);
			}
		}
		return $lJoinedTables." ";
	}
	
}