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

class SelectQuery {

	const INNER_JOIN = 'inner join';
	const LEFT_JOIN  = 'left join';
	const RIGHT_JOIN = 'right join';
	const FULL_JOIN  = 'full join';
	
	const ASC  = 'ASC';
	const DESC = 'DESC';
	
	private static $sAccpetedJoins = [
		self::INNER_JOIN => null,
		self::LEFT_JOIN  => null,
		self::RIGHT_JOIN => null,
		self::FULL_JOIN  => null
	];
	
	private static $sAccpetedOrders = [
			self::ASC  => null,
			self::DESC => null
	];

	private $mMainTable;
	private $mCurrentTableName;
	private $mWhere;
	private $mHaving;
	private $mLimit;
	private $mOffset;
	
	private $mJoinedTables = [];
	private $mTableByName  = [];
	private $mOrder        = [];
	private $mGroup        = [];
	
	/**
	 * 
	 * @param string|TableNode $pTable
	 */
	public function __construct($pTable) {
		$this->_setMainTable($pTable);
	}
	
	/**
	 *
	 * @param string|TableNode $pTable
	 */
	public function init($pTable) {
		$this->mMainTable        = null;
		$this->mJoinedTables     = [];
		$this->mTableByName      = [];
		$this->mCurrentTableName = null;
		$this->mOrder            = [];
		$this->mGroup            = [];
		$this->mLimit            = null;
		$this->mOffset           = null;
		$this->mWhere            = null;
		$this->mHaving           = null;
		$this->_setMainTable($pTable);
		return $this;
	}
	
	/**
	 * 
	 * @param string|TableNode $pTable
	 */
	private function _setMainTable($pTable) {
		if (is_string($pTable)) {
			$this->mMainTable = new TableNode($pTable);
		} else if ($pTable instanceof TableNode) {
			$this->mMainTable = $pTable;
		} else {
			throw new \Exception('invalid parameter table, should be string or instance of TableNode');
		}
		$this->mTableByName[$this->mMainTable->getExportName()] = $this->mMainTable;
		$this->mCurrentTableName = $this->mMainTable->getExportName();
	}
	
	/**
	 *
	 * @return TableNode $pTable
	 */
	public function getMainTable() {
		return $this->mMainTable;
	}
	
	/**
	 * 
	 * @param string $pCurrentTableName export name of a table
	 * @return boolean true if success i.e. if wanted table has been added
	 */
	public function setCurrentTable($pCurrentTableName) {
		if (!array_key_exists($pCurrentTableName, $this->mTableByName)) {
			return false;
		}
		$this->mCurrentTableName = $pCurrentTableName;
		return true;
	}
	
	/**
	 * get the export name of current table
	 * @return string
	 */
	public function getCurrentTableName() {
		return $this->mCurrentTableName;
	}
	
	/**
	 * get current table
	 * @return string
	 */
	public function getCurrentTable() {
		return $this->mTableByName[$this->mCurrentTableName];
	}
	
	/**
	 * 
	 * @return SelectQuery
	 */
	public function setMainTableAsCurrentTable() {
		reset($this->mTableByName);
		$this->mCurrentTableName = key($this->mTableByName);
		return $this;
	}
	
	/**
	 * 
	 * @param string $pJoinType must be in array self::$sAccpetedJoins
	 * @param string|TableNode $pTable
	 * @param OnLiteral|OnLogicalJunction $pOn determine on which colmuns join will be applied
	 */
	public function join($pJoinType, $pTable, $pOn) {
		if ($pTable instanceof TableNode) {
			$lTable = $pTable;
			$lTableName = $pTable->getExportName();
		} else {
			$lTable = new TableNode($pTable);
			$lTableName = $pTable;
		}
		
		if (!array_key_exists($pJoinType, self::$sAccpetedJoins)) {
			throw new \Exception("undefined join type '$pJoinType'");
		}
		if (array_key_exists($lTableName, $this->mTableByName)) {
			throw new \Exception("table already added '$lTableName'");
		}
		$this->mTableByName[$lTableName] = $lTable;
		$this->mCurrentTableName = $lTableName;
		$this->mJoinedTables[] = [$lTable, $pJoinType, $pOn];
		return $lTable;
	}
	
	/**
	 * 
	 * @param WhereLiteral|WhereLogicalJunction $pWhere
	 * @return SelectQuery
	 */
	public function where($pWhere) {
		$this->mWhere = $pWhere;
		return $this;
	}
	
	/**
	 *
	 * @return WhereLiteral|WhereLogicalJunction
	 */
	public function getWhere() {
		return $this->mWhere;
	}
	
	/**
	 *
	 * @param HavingLiteral|HavingLogicalJunction $pHaving
	 * @return SelectQuery
	 */
	public function having($pHaving) {
		$this->mHaving = $pHaving;
		return $this;
	}
	
	/**
	 *
	 * @return HavingLiteral|HavingLogicalJunction
	 */
	public function getHaving() {
		return $this->mHaving;
	}
	
	public function resetGroupColumns() {
		$this->mGroup = [];
	}
	
	public function addGroupColumn($pColumn) {
		$this->mGroup[] = $this->mCurrentTableName.'.'.$pColumn;
		return $this;
	}
	
	public function resetOrderColumns() {
		$this->mOrder = [];
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
	
	public function limit($pLimit) {
		$this->mLimit = $pLimit;
		return $this;
	}
	
	public function offset($pOffset) {
		$this->mOffset = $pOffset;
		return $this;
	}
	
	public function export() {
		$lValues = [];
	
		$lQuery = 'SELECT '.$this->_getColumnsForQuery().' FROM '.$this->_exportJoinedTables($lValues);
	
		if (!is_null($lClause = $this->_getClauseForQuery($this->mWhere, $lValues))) {
			$lQuery .= ' WHERE '.$lClause;
		}
		if (!empty($this->mGroup)) {
			$lQuery .= ' GROUP BY '.implode(',', $this->mGroup);
		}
		if (!empty($this->mOrder)) {
			$lQuery .= ' ORDER BY '.implode(',', $this->mOrder);
		}
		if (!is_null($lClause = $this->_getClauseForQuery($this->mHaving, $lValues))) {
			$lQuery .= ' HAVING '.$lClause;
		}
		if (!is_null($this->mLimit)) {
			if (empty($this->mOrder)) {
				trigger_error('Warning, limit is used without ordering');
			}
			$lQuery .= ' LIMIT '.$this->mLimit;
		}
		if (!is_null($this->mOffset)) {
			if (empty($this->mOrder)) {
				trigger_error('Warning, offset is used without ordering');
			}
			$lQuery .= ' OFFSET '.$this->mOffset;
		}
		return [$lQuery, $lValues];
	}
	
	public function exportWithValue() {
		list($lQuery, $lValues) = $this->export();
		return vsprintf(str_replace('?', '%s', $lQuery), $lValues);
	}
	
	private function _getColumnsForQuery() {
		$lSelectAllColumns = true;
		foreach ($this->mTableByName as $lTable) {
			if (!$lTable->areAllColumnsSelected()) {
				$lSelectAllColumns = false;
				break;
			}
		}
		if ($lSelectAllColumns) {
			return '*';
		} else {
			$lArray = [];
			foreach ($this->mTableByName as $lTable) {
				if ($lTable->hasSelectedColumns()) {
					$lArray[] = $lTable->exportSelectedColumns();
				}
			}
			return implode(',', $lArray);
		}
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
			if ($lQueryLiterals != '') {
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
		list($lExportedTable, $lSubValues) = $this->mMainTable->exportTable();
		$lJoinedTables = ' '.$lExportedTable;
		
		foreach ($this->mJoinedTables as $lJoinedTable) {
			list($lExportedTable, $lSubValues) = $lJoinedTable[0]->exportTable();
			$lValues = array_merge($lValues, $lSubValues);
			
			$lJoinedTables .= ' '.$lJoinedTable[1];
			$lJoinedTables .= ' '.$lExportedTable;
			$lJoinedTables .= ' on '.$lJoinedTable[2]->export($lValues);
		}
		
		return $lJoinedTables.' ';
	}
	
}