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
	
	private static $acceptedJoins = [
		self::INNER_JOIN => null,
		self::LEFT_JOIN  => null,
		self::RIGHT_JOIN => null,
		self::FULL_JOIN  => null
	];
	
	private static $acceptedOrders = [
			self::ASC  => null,
			self::DESC => null
	];

	private $mainTable;
	private $currentTableName;
	private $where;
	private $having;
	private $limit;
	private $offset;
	
	private $joinedTables = [];
	private $tableByName  = [];
	private $order        = [];
	private $group        = [];
	
	/**
	 * 
	 * @param string|TableNode $table
	 */
	public function __construct($table) {
		$this->_setMainTable($table);
	}
	
	/**
	 *
	 * @param string|TableNode $table
	 */
	public function init($table) {
		$this->mainTable        = null;
		$this->joinedTables     = [];
		$this->tableByName      = [];
		$this->currentTableName = null;
		$this->order            = [];
		$this->group            = [];
		$this->limit            = null;
		$this->offset           = null;
		$this->where            = null;
		$this->having           = null;
		$this->_setMainTable($table);
		return $this;
	}
	
	/**
	 * 
	 * @param string|TableNode $table
	 */
	private function _setMainTable($table) {
		if (is_string($table)) {
			$this->mainTable = new TableNode($table);
		} else if ($table instanceof TableNode) {
			$this->mainTable = $table;
		} else {
			throw new \Exception('invalid parameter table, should be string or instance of TableNode');
		}
		$this->tableByName[$this->mainTable->getExportName()] = $this->mainTable;
		$this->currentTableName = $this->mainTable->getExportName();
	}
	
	/**
	 *
	 * @return TableNode $table
	 */
	public function getMainTable() {
		return $this->mainTable;
	}
	
	/**
	 * 
	 * @param string $currentTableName export name of a table
	 * @return boolean true if success i.e. if wanted table has been added
	 */
	public function setCurrentTable($currentTableName) {
		if (!array_key_exists($currentTableName, $this->tableByName)) {
			return false;
		}
		$this->currentTableName = $currentTableName;
		return true;
	}
	
	/**
	 * get the export name of current table
	 * @return string
	 */
	public function getCurrentTableName() {
		return $this->currentTableName;
	}
	
	/**
	 * get current table
	 * @return string
	 */
	public function getCurrentTable() {
		return $this->tableByName[$this->currentTableName];
	}
	
	/**
	 * 
	 * @return SelectQuery
	 */
	public function setMainTableAsCurrentTable() {
		reset($this->tableByName);
		$this->currentTableName = key($this->tableByName);
		return $this;
	}
	
	/**
	 * 
	 * @param string $joinType must be in array self::$acceptedJoins
	 * @param string|TableNode $table
	 * @param OnLiteral|OnLogicalJunction $on determine on which colmuns join will be applied
	 */
	public function join($joinType, $table, $on) {
		if ($table instanceof TableNode) {
			$tableNode = $table;
			$tableName = $table->getExportName();
		} else {
			$tableNode = new TableNode($table);
			$tableName = $table;
		}
		
		if (!array_key_exists($joinType, self::$acceptedJoins)) {
			throw new \Exception("undefined join type '$joinType'");
		}
		if (array_key_exists($tableName, $this->tableByName)) {
			throw new \Exception("table already added '$tableName'");
		}
		$this->tableByName[$tableName] = $tableNode;
		$this->currentTableName = $tableName;
		$this->joinedTables[] = [$tableNode, $joinType, $on];
		return $tableNode;
	}
	
	/**
	 * 
	 * @param WhereLiteral|WhereLogicalJunction $where
	 * @return SelectQuery
	 */
	public function where($where) {
		$this->where = $where;
		return $this;
	}
	
	/**
	 *
	 * @return WhereLiteral|WhereLogicalJunction
	 */
	public function getWhere() {
		return $this->where;
	}
	
	/**
	 *
	 * @param HavingLiteral|HavingLogicalJunction $having
	 * @return SelectQuery
	 */
	public function having($having) {
		$this->having = $having;
		return $this;
	}
	
	/**
	 *
	 * @return HavingLiteral|HavingLogicalJunction
	 */
	public function getHaving() {
		return $this->having;
	}
	
	public function resetGroupColumns() {
		$this->group = [];
	}
	
	public function addGroupColumn($column) {
		$this->group[] = $this->currentTableName.'.'.$column;
		return $this;
	}
	
	public function resetOrderColumns() {
		$this->order = [];
	}
	
	public function addOrderColumn($column, $type = self::ASC) {
		if (!array_key_exists($type, self::$acceptedOrders)) {
			throw new \Exception("undefined order type '$type'");
		}
		$order = $this->currentTableName.'.'.$column;
		if ($type == self::DESC) {
			$order .= " $type";
		}
		$this->order[] = $order;
		return $this;
	}
	
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}
	
	public function export() {
		$values = [];
	
		$query = 'SELECT '.$this->_getColumnsForQuery().' FROM '.$this->_exportJoinedTables($values);
	
		if (!is_null($clause = $this->_getClauseForQuery($this->where, $values))) {
			$query .= ' WHERE '.$clause;
		}
		if (!empty($this->group)) {
			$query .= ' GROUP BY '.implode(',', $this->group);
		}
		if (!empty($this->order)) {
			$query .= ' ORDER BY '.implode(',', $this->order);
		}
		if (!is_null($clause = $this->_getClauseForQuery($this->having, $values))) {
			$query .= ' HAVING '.$clause;
		}
		if (!is_null($this->limit)) {
			if (empty($this->order)) {
				trigger_error('Warning, limit is used without ordering');
			}
			$query .= ' LIMIT '.$this->limit;
		}
		if (!is_null($this->offset)) {
			if (empty($this->order)) {
				trigger_error('Warning, offset is used without ordering');
			}
			$query .= ' OFFSET '.$this->offset;
		}
		return [$query, $values];
	}
	
	public function exportWithValue() {
		list($query, $values) = $this->export();
		return vsprintf(str_replace('?', '%s', $query), $values);
	}
	
	private function _getColumnsForQuery() {
		$selectAllColumns = true;
		foreach ($this->tableByName as $table) {
			if (!$table->areAllColumnsSelected()) {
				$selectAllColumns = false;
				break;
			}
		}
		if ($selectAllColumns) {
			return '*';
		} else {
			$array = [];
			foreach ($this->tableByName as $table) {
				if ($table->hasSelectedColumns()) {
					$array[] = $table->exportSelectedColumns();
				}
			}
			return implode(',', $array);
		}
	}
	
	/**
	 * construct clause query (WHERE ...), extract values for query and put them in $values
	 * @param LogicalJunction $logicalJunction
	 * @param array $values
	 * @return string
	 */
	private function _getClauseForQuery($logicalJunction, &$values) {
		$clause = null;
		if (!is_null($logicalJunction)) {
			$queryLiterals = $logicalJunction->export($values);
			if ($queryLiterals != '') {
				$clause = $queryLiterals;
			}
		}
		return $clause;
	}
	
	/**
	 * @param array $values
	 * @return string
	 */
	private function _exportJoinedTables(&$values) {
		list($exportedTable, $subValues) = $this->mainTable->exportTable();
		$joinedTables = ' '.$exportedTable;
		
		foreach ($this->joinedTables as $joinedTable) {
			list($exportedTable, $subValues) = $joinedTable[0]->exportTable();
			$values = array_merge($values, $subValues);
			
			$joinedTables .= ' '.$joinedTable[1];
			$joinedTables .= ' '.$exportedTable;
			$joinedTables .= ' on '.$joinedTable[2]->export($values);
		}
		
		return $joinedTables.' ';
	}
	
}