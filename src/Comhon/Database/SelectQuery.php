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

use Comhon\Logic\Formula;
use Comhon\Exception\ComhonException;

class SelectQuery {

	/** @var string */
	const INNER_JOIN = 'inner join';
	
	/** @var string */
	const LEFT_JOIN  = 'left join';
	
	/** @var string */
	const RIGHT_JOIN = 'right join';
	
	/** @var string */
	const FULL_JOIN  = 'full join';
	
	/** @var string */
	const ASC  = 'ASC';
	
	/** @var string */
	const DESC = 'DESC';
	
	/** @var array */
	private static $allowedJoins = [
		self::INNER_JOIN => null,
		self::LEFT_JOIN  => null,
		self::RIGHT_JOIN => null,
		self::FULL_JOIN  => null
	];
	
	/** @var array */
	private static $allowedOrders = [
			self::ASC  => null,
			self::DESC => null
	];
	
	/** @var TableNode[] */
	private $tables = [];
	
	/** @var TableNode */
	private $tableFocus;
	
	/** @var array */
	private $joinedTables = [];

	/** @var \Comhon\Logic\Formula */
	private $where;
	
	/** @var \Comhon\Logic\Formula */
	private $having;
	
	/** @var integer */
	private $limit;
	
	/** @var integer */
	private $offset;
	
	/** @var array */
	private $order = [];
	
	/** @var array */
	private $group = [];
	
	/**
	 * 
	 * @param string|TableNode $table
	 */
	public function __construct($table) {
		$this->_setMainTable($table);
	}
	
	/**
	 * get allowed order types
	 * 
	 * @return string[]
	 */
	public static function getAllowedOrderTypes() {
		return array_keys(self::$allowedOrders);
	}
	
	/**
	 * verify if order type is allowed
	 *
	 * @param string $type
	 * @return boolean
	 */
	public static function isAllowedOrderType($type) {
		return array_key_exists($type, self::$allowedOrders);
	}
	
	/**
	 * initialize new select query (reset all previous settings)
	 *
	 * @param string|TableNode $table
	 * @return \Comhon\Database\SelectQuery
	 */
	public function initialize($table) {
		$this->joinedTables = [];
		$this->order        = [];
		$this->group        = [];
		$this->limit        = null;
		$this->offset       = null;
		$this->where        = null;
		$this->having       = null;
		$this->_setMainTable($table);
		return $this;
	}
	
	/**
	 * @param string|TableNode $table
	 */
	private function _setMainTable($table) {
		if (is_string($table)) {
			$mainTable = new TableNode($table);
		} else if ($table instanceof TableNode) {
			$mainTable = $table;
		} else {
			throw new ComhonException('invalid parameter table, should be string or instance of TableNode');
		}
		$this->tables = [$mainTable];
		$this->tableFocus = $mainTable;
	}
	
	/**
	 * get main requested table
	 *
	 * @return TableNode
	 */
	public function getMainTable() {
		return $this->tables[0];
	}
	
	/**
	 * set focus on specified table
	 * 
	 * @param TableNode|string $table
	 * @return boolean false if specified table not already added in SelectQuery
	 */
	public function setTableFocus($table) {
		if (is_string($table)) {
			foreach ($this->tables as $tableInQuery) {
				if ($tableInQuery->getExportName() === $table) {
					$this->tableFocus = $tableInQuery;
					return true;
				}
			}
		} else if ($table instanceof TableNode) {
			foreach ($this->tables as $tableInQuery) {
				if ($tableInQuery === $table) {
					$this->tableFocus = $tableInQuery;
					return true;
				}
			}
		} else {
			throw new ComhonException('bad first parameter should be string or instance of TabelNode');
		}
		return false;
	}
	
	/**
	 * get the focused table
	 * 
	 * @return TableNode
	 */
	public function getTableFocus() {
		return $this->tableFocus;
	}
	
	/**
	 * set focus on main table
	 * 
	 * @return SelectQuery
	 */
	public function setFocusOnMainTable() {
		$this->tableFocus = $this->tables[0];
		return $this;
	}
	
	/**
	 * join table to query
	 * 
	 * update focus on new joined table
	 * 
	 * @param string $joinType 
	 *            [self::INNER_JOIN, self::LEFT_JOIN, self::RIGHT_JOIN, self::FULL_JOIN]
	 * @param string|TableNode $table
	 * @param \Comhon\Logic\Formula $on determine on which colmuns join will be applied
	 * @return \Comhon\Database\TableNode
	 */
	public function join($joinType, $table, $on) {
		if ($table instanceof TableNode) {
			$tableNode = $table;
			$tableName = $table->getExportName();
		} else {
			$tableNode = new TableNode($table);
			$tableName = $table;
		}
		if (!array_key_exists($joinType, self::$allowedJoins)) {
			throw new ComhonException("undefined join type '$joinType'");
		}
		$this->tables[] = $tableNode;
		$this->tableFocus = $tableNode;
		$this->joinedTables[] = [$tableNode, $joinType, $on];
		return $tableNode;
	}
	
	/**
	 * set where conditions
	 * 
	 * @param \Comhon\Logic\Formula $where
	 * @return SelectQuery
	 */
	public function where(Formula $where) {
		$this->where = $where;
		return $this;
	}
	
	/**
	 * set having conditions
	 *
	 * @param \Comhon\Logic\Formula $having
	 * @return SelectQuery
	 */
	public function having($having) {
		$this->having = $having;
		return $this;
	}
	
	/**
	 * reset group columns
	 */
	public function resetGroupColumns() {
		$this->group = [];
	}
	
	/**
	 * add group column on focused table
	 * 
	 * @param string $column
	 * @return \Comhon\Database\SelectQuery
	 */
	public function addGroup($column) {
		$this->group[] = [$this->tableFocus, $column];
		return $this;
	}
	
	/**
	 * reset order columns
	 */
	public function resetOrderColumns() {
		$this->order = [];
	}
	
	/**
	 * add order column on focused table
	 * 
	 * @param string $column
	 * @param string $type [self::ASC, self::DESC]
	 * @throws \Exception
	 * @return \Comhon\Database\SelectQuery
	 */
	public function addOrder($column, $type = self::ASC) {
		if (!array_key_exists($type, self::$allowedOrders)) {
			throw new ComhonException("undefined order type '$type'");
		}
		$order = $type == self::ASC ? $column : $column . " $type";
		$this->order[] = [$this->tableFocus, $order];
		return $this;
	}
	
	/**
	 * set limit
	 * 
	 * @param integer $limit
	 * @return \Comhon\Database\SelectQuery
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	/**
	 * set offset
	 * 
	 * @param integer $offset
	 * @return \Comhon\Database\SelectQuery
	 */
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}
	
	/**
	 * export stringified query in sql format with values to bind
	 * 
	 * @throws \Exception
	 * @return array 
	 *     - first element : the query
	 *     - second element : values to bind
	 */
	public function export() {
		$tables = [];
		foreach ($this->tables as $tableInQuery) {
			if (array_key_exists($tableInQuery->getExportName(), $tables)) {
				throw new ComhonException("duplicate table '{$tableInQuery->getExportName()}'");
			}
			$tables[$tableInQuery->getExportName()] = null;
		}
		
		$values = [];
	
		$query = 'SELECT '.$this->_getColumnsForQuery().' FROM '.$this->_exportJoinedTables($values);
	
		if (!is_null($this->where) && !is_null($conditions = $this->_getConditions($this->where, $values))) {
			$query .= ' WHERE '.$conditions;
		}
		if (!empty($this->group)) {
			$group = [];
			foreach ($this->group as $groupPart) {
				$group[] = $groupPart[0]->getExportName() . '.' . $groupPart[1];
			}
			$query .= ' GROUP BY '.implode(',', $group);
		}
		if (!empty($this->order)) {
			$order = [];
			foreach ($this->order as $orderPart) {
				$order[] = $orderPart[0]->getExportName() . '.' . $orderPart[1];
			}
			$query .= ' ORDER BY '.implode(',', $order);
		}
		if (!is_null($this->having) && !is_null($conditions = $this->_getConditions($this->having, $values))) {
			$query .= ' HAVING '.$conditions;
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
	
	/**
	 * export stringified query in sql format
	 * DO NOT USE this function to build a query that will be executed
	 * USE this function to see what query looks like
	 *
	 * @return string
	 */
	public function exportDebug() {
		list($query, $values) = $this->export();
		return vsprintf(str_replace('?', '%s', $query), $values);
	}
	
	
	/**
	 * get stringified selected columns
	 * 
	 * @return string
	 */
	private function _getColumnsForQuery() {
		$selectAllColumns = true;
		foreach ($this->tables as $table) {
			if (!$table->areAllColumnsSelected()) {
				$selectAllColumns = false;
				break;
			}
		}
		if ($selectAllColumns) {
			return '*';
		} else {
			$array = [];
			foreach ($this->tables as $table) {
				if ($table->hasSelectedColumns()) {
					$array[] = $table->exportSelectedColumns();
				}
			}
			return implode(',', $array);
		}
	}
	
	/**
	 * export stringified conditions query, extract values for query and put them in $values
	 * 
	 * @param \Comhon\Logic\Formula $formula
	 * @param mixed[] $values
	 * @return string|null
	 */
	private function _getConditions(Formula $formula, &$values) {
		$conditions = null;
		$result = $formula->export($values);
		if ($result != '') {
			$conditions = $result;
		}
		return $conditions ;
	}
	
	/**
	 * export strigified joins, extract values for query and put them in $values
	 * 
	 * @param array $values
	 * @return string
	 */
	private function _exportJoinedTables(&$values) {
		list($exportedTable, $subValues) = $this->tables[0]->exportTable();
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