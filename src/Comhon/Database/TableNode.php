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

use Comhon\Exception\ComhonException;

class TableNode {

	/** @var string */
	private $table;
	
	/** @var string */
	private $alias;
	
	/** @var string[] */
	private $selectedColumns = [];
	
	/** @var boolean */
	private $selectAllColumns = true;
	
	/**
	 * 
	 * @param string|SelectQuery $table
	 * @param string $alias
	 * @param boolean $selectAllColumns
	 */
	public function __construct($table, $alias = null, $selectAllColumns = true) {
		if (($table instanceof SelectQuery) && is_null($alias)) {
			throw new ComhonException('TableNode must have an alias if specified table is an instance of SelectQuery');
		}
		$this->table = $table;
		$this->alias = $alias;
		$this->selectAllColumns = $selectAllColumns;
	}
	
	/**
	 * get table name
	 * 
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * get alias table if set
	 *
	 * @return string|null null if alias not set
	 */
	public function getAlias() {
		return $this->alias;
	}
	
	/**
	 * set alias table
	 *
	 * @param string $alias
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
	}
	
	/**
	 * get table name or alias if exists
	 * 
	 * @return string
	 */
	public function getExportName() {
		return is_null($this->alias) ? $this->table : $this->alias;
	}
	
	/**
	 * add selected column
	 * 
	 * @param string $column
	 * @param string $alias
	 * @return TableNode
	 */
	public function addSelectedColumn($column, $alias = null) {
		$this->selectAllColumns = false;
		$this->selectedColumns[] = is_null($alias) ? $column : $column . ' AS ' . $alias;;
		return $this;
	}
	
	/**
	 * all selected columns previously set will be reset
	 * 
	 * @return TableNode
	 */
	public function resetSelectedColumns() {
		$this->selectedColumns = [];
		return $this;
	}
	
	/**
	 * determine if all columns will be selected or not
	 * 
	 * @param boolean $column if true, all selected columns previously set will be reset
	 * @return TableNode
	 */
	public function selectAllColumns($boolean) {
		if ($boolean) {
			$this->selectedColumns = [];
		}
		$this->selectAllColumns = $boolean;
		return $this;
	}
	
	/**
	 * verify if all columns will be exported
	 * 
	 * @return boolean
	 */
	public function areAllColumnsSelected() {
		return $this->selectAllColumns;
	}
	
	/**
	 * verify if all columns will be exported
	 * 
	 * @return boolean
	 */
	public function hasSelectedColumns() {
		return $this->selectAllColumns || !empty($this->selectedColumns);
	}
	
	/**
	 * export selected columns as sql format
	 * 
	 * @return string
	 */
	public function exportSelectedColumns() {
		$exportName = is_null($this->alias) ? $this->table : $this->alias;
		if ($this->selectAllColumns) {
			return $exportName.'.*';
		}
		$columns = [];
		foreach ($this->selectedColumns as $selectedColumn) {
			$columns[] = $exportName . '.' . $selectedColumn;
		}
		return implode(', ', $columns);
	}
	
	/**
	 * export stringified table in sql format with values to bind
	 *
	 * @return array
	 *     - first element : the table exported in sql format
	 *     - second emement : values to bind
	 */
	public function exportTable() {
		if ($this->table instanceof SelectQuery) {
			list($selectQuery, $values) = $this->table->export();
			$export = " ($selectQuery) AS $this->alias";
		} else {
			$values = [];
			$export = is_null($this->alias) ? $this->table : $this->table . ' AS ' . $this->alias;
		}
		return [$export, $values];
	}
}