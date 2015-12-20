<?php
namespace objectManagerLib\database;

class SelectQuery {

	const INNER_JOIN = "inner join";
	const LEFT_JOIN  = "left join";
	const RIGHT_JOIN = "right join";
	const FULL_JOIN  = "full join";
	
	const ASC  = "ASC";
	const DESC = "DESC";
	
	private static $sAccpetedJoins = array(
		self::INNER_JOIN => null,
		self::LEFT_JOIN  => null,
		self::RIGHT_JOIN => null,
		self::FULL_JOIN  => null
	);
	
	private static $sAccpetedOrders = array(
			self::ASC  => null,
			self::DESC => null
	);

	private $mFirstTable;
	private $mCurrentTableName;
	private $mColumnsByTable;
	private $mWhereLogicalJunction;
	private $mHavingLogicalJunction;
	private $mLimit;
	private $mOffset;
	
	private $mJoinedTables = array();
	private $mTableNames   = array();
	private $mOrder        = array();
	private $mGroup        = array();
	
	/**
	 * 
	 * @param unknown $pTable
	 * @param unknown $pAlias
	 */
	public function __construct($pTable, $pAlias = null) {
		$this->_setFirstTable($pTable, $pAlias);
	}
	
	public function init($pTable, $pAlias = null) {
		$this->mFirstTable       = null;
		$this->mJoinedTables     = array();
		$this->mTableNames       = array();
		$this->mCurrentTableName = null;
		$this->mColumnsByTable   = null;
		$this->mOrder            = array();
		$this->mGroup            = array();
		$this->mLimit            = null;
		$this->mOffset           = null;
		$this->mWhereLogicalJunction  = null;
		$this->mHavingLogicalJunction = null;
		$this->_setFirstTable($pTable, $pAlias);
		return $this;
	}
	
	private function _setFirstTable($pTable, $pAlias) {
		$this->mFirstTable = is_null($pAlias) ? array($pTable) : array($pTable, $pAlias);
		$this->mTableNames[$this->mFirstTable[count($this->mFirstTable) - 1]] = null;
		$this->mCurrentTableName = $this->mFirstTable[count($this->mFirstTable) - 1];
	}
	
	public function getCurrentTableName() {
		return $this->mCurrentTableName;
	}
	
	public function setCurrentTable($pCurrentTableName) {
		if (!array_key_exists($pCurrentTableName, $this->mTableNames)) {
			return false;
		}
		$this->mCurrentTableName = $pCurrentTableName;
		return true;
	}
	
	public function getCurrentTable() {
		return $this->mCurrentTableName;
	}
	
	public function setFirstTableCurrentTable() {
		reset($this->mTableNames);
		$this->mCurrentTableName = key($this->mTableNames);
		return $this;
	}
	
	/**
	 * 
	 * @param string|SelectQuery $pTable
	 * @param string $pAlias if you don't want alias, put null value
	 * @param string $pJoinType must be in array self::$sAccpetedJoins
	 * @param string|array $pColumn can be a column or an array of columns
	 * @param string $pForeignColumn must reference a column of a table already added
	 * @param string $pForeignTable must reference a table name or an alias already added
	 */
	public function addTable($pTable, $pAlias, $pJoinType, $pColumn, $pForeignColumn, $pForeignTable) {
		if (is_object($pTable) && is_null($pAlias)) {
			throw new \Exception("object table must have an alias");
		}
		if (!array_key_exists($pForeignTable, $this->mTableNames)) {
			throw new \Exception("foreign table '$pForeignTable' is not already added ".json_encode(array_keys($this->mTableNames)));
		}
		if (!array_key_exists($pJoinType, self::$sAccpetedJoins)) {
			throw new \Exception("undefined join type '$pJoinType'");
		}
		$lTable = is_null($pAlias) ? array($pTable) : array($pTable, $pAlias);
		$lTableName = $lTable[count($lTable) - 1];
		if (array_key_exists($lTableName, $this->mTableNames)) {
			throw new \Exception("table already added '$lTableName'");
		}
		$this->mTableNames[$lTableName] = null;
		$this->mCurrentTableName = $lTableName;
		$this->mJoinedTables[] = array(
			$pJoinType,
			$lTable,
			array($lTableName, $pColumn),
			array($pForeignTable, $pForeignColumn)
		);
		return $this;
	}
	
	public function resetSelectColumns() {
		$this->mColumnsByTable = array();
	}
	
	public function addSelectColumn($pColumn, $pAlias = null) {
		$lColumn = array($pColumn);
		if (!is_null($pAlias)) {
			$lColumn[] = $pAlias;
		}
		$this->mColumnsByTable[$this->mCurrentTableName][] = $lColumn;
		return $this;
	}
	
	public function setWhereLogicalJunction($pWhereLogicalJunction) {
		$this->mWhereLogicalJunction = $pWhereLogicalJunction;
		return $this;
	}
	
	public function getWhereLogicalJunction() {
		return $this->mWhereLogicalJunction;
	}
	
	public function setHavingLogicalJunction($pHavingLogicalJunction) {
		$this->mHavingLogicalJunction = $pHavingLogicalJunction;
		return $this;
	}
	
	public function getHavingLogicalJunction() {
		return $this->mHavingLogicalJunction;
	}
	
	public function resetGroupColumns() {
		$this->mGroup = array();
	}
	
	public function addGroupColumn($pColumn) {
		$this->mGroup[] = $this->mCurrentTableName.'.'.$pColumn;
		return $this;
	}
	
	public function addOrderColumn($pColumn, $pType = self::ASC) {
		if (!array_key_exists($pType, self::$sAccpetedOrders)) {
			throw new \Exception("undefined order type '$pType'");
		}
		$lOrder = $this->mCurrentTableName.'.'.$pColumn;
		if ($pType == self::DESC) {
			$lOrder .= " $pType";
		}
		$this->mOrder[] = $lOrder;
		return $this;
	}
	
	public function resetOrderColumns() {
		$this->mOrder = array();
	}
	
	public function setLimit($pLimit) {
		$this->mLimit = $pLimit;
		return $this;
	}
	
	public function setOffset($pOffset) {
		$this->mOffset = $pOffset;
		return $this;
	}
	
	public function export() {
		$lValues = array();
	
		$lColumns = (count($this->mColumnsByTable) == 0) ? "*" : $this->_getColumnsForQuery();
		$lQuery = "SELECT ".$lColumns." FROM ".$this->_exportJoinedTables($lValues);
	
		if (!is_null($lClause = $this->_getClauseForQuery($this->mWhereLogicalJunction, $lValues))) {
			$lQuery .= " WHERE ".$lClause;
		}
		if (count($this->mGroup) > 0) {
			$lQuery .= " GROUP BY ".implode(",", $this->mGroup);
		}
		if (count($this->mOrder) > 0) {
			$lQuery .= " ORDER BY ".implode(",", $this->mOrder);
		}
		if (!is_null($lClause = $this->_getClauseForQuery($this->mHavingLogicalJunction, $lValues))) {
			$lQuery .= " HAVING ".$lClause;
		}
		if (!is_null($this->mLimit)) {
			if (count($this->mOrder) == 0) {
				trigger_error('Warning, limit is used without ordering');
			}
			$lQuery .= " LIMIT ".$this->mLimit;
		}
		if (!is_null($this->mOffset)) {
			if (count($this->mOrder) == 0) {
				trigger_error('Warning, offset is used without ordering');
			}
			$lQuery .= " OFFSET ".$this->mOffset;
		}
		return array($lQuery, $lValues);
	}
	
	public function exportWithValue() {
		list($lQuery, $lValues) = $this->export();
		return vsprintf(str_replace('?', "%s", $lQuery), $lValues);
	}
	
	private function _getColumnsForQuery() {
		$lArray = array();
		foreach ($this->mColumnsByTable as $lTable => $lColumns) {
			foreach ($lColumns as $lColumn) {
				$lArray[] = sprintf("%s.%s", $lTable, implode(" as ", $lColumn));
			}
		}
		return implode(",", $lArray);
	}
	
	/**
	 * construct clause query (WHERE ...), extract values for query and put them in $pValues
	 * @param LogicalJunction $pLogicalJunction
	 * @param array $pValues
	 * @return string
	 */
	private function _getClauseForQuery($pLogicalJunction, &$pValues) {
		$lClause = null;
		if (!is_null($pLogicalJunction)) {
			$lQueryLiterals = $pLogicalJunction->export($pValues);
			if ($lQueryLiterals != "") {
				$lClause = $lQueryLiterals;
			}
		}
		return $lClause;
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	private function _exportJoinedTables(&$lValues) {
		$lJoinedTables = " ".implode(" as ", $this->mFirstTable);
		foreach ($this->mJoinedTables as $lJoinedTable) {
			$lJoinedTables .= " ".$lJoinedTable[0];
			if (is_object($lJoinedTable[1][0])) {
				list($lSubquery, $lSubValues) = $lJoinedTable[1][0]->export();
				$lValues        = array_merge($lValues, $lSubValues);
				$lJoinedTables .= " ($lSubquery) as {$lJoinedTable[1][1]}";
			} else {
				$lJoinedTables .= " ".implode(" as ", $lJoinedTable[1]);
			}
			if (is_array($lJoinedTable[2][1])) {
				$lOnLiterals = array();
				foreach ($lJoinedTable[2][1] as $lRightColumn) {
					$lOnLiterals[] = $lJoinedTable[2][0].".".$lRightColumn."=".implode(".", $lJoinedTable[3]);
				}
				$lJoinedTables .= " on ".implode(" or ", $lOnLiterals);
			} else {
				$lJoinedTables .= " on ".implode(".", $lJoinedTable[2])."=".implode(".", $lJoinedTable[3]);
			}
		}
		return $lJoinedTables." ";
	}
	
}