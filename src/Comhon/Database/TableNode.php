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

class TableNode {

	private $table;
	private $alias;
	private $exportName;
	private $selectedColumns = [];
	private $selectAllColumns = true;
	
	/**
	 * 
	 * @param string|SelectQuery $table
	 * @param string $alias
	 */
	public function __construct($table, $alias = null, $selectAllColumns = true) {
		if (($table instanceof SelectQuery) && is_null($alias)) {
			throw new \Exception('TableNode must have an alias if specified table is an instance of SelectQuery');
		}
		$this->table = $table;
		$this->alias = $alias;
		$this->exportName = is_null($this->alias) ? $this->table : $this->alias;
		$this->selectAllColumns = $selectAllColumns;
	}
	
	/**
	 * 
	 * @return string return table name or alias if exists
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 *
	 * @return string return table name or alias if exists
	 */
	public function getExportName() {
		return $this->exportName;
	}
	
	/**
	 * add selected column
	 * if export all columns has been set to true, it is automaticaly set to false
	 * @param string $column
	 * @param string $alias
	 * @return TableNode
	 */
	public function addSelectedColumn($column, $alias = null) {
		$this->selectAllColumns = false;
		$this->selectedColumns[] = is_null($alias) 
			? $this->exportName . '.' . $column : $this->exportName . '.' . $column . ' AS ' . $alias;
		return $this;
	}
	
	/**
	 * all selected columns previously set will be reset
	 * @return TableNode
	 */
	public function resetSelectedColumns() {
		$this->selectedColumns = [];
		return $this;
	}
	
	/**
	 * determine if all columns will be selected or not
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
	 * @return boolean
	 */
	public function areAllColumnsSelected() {
		return $this->selectAllColumns;
	}
	
	/**
	 * verify if all columns will be exported
	 * @return boolean
	 */
	public function hasSelectedColumns() {
		return $this->selectAllColumns || !empty($this->selectedColumns);
	}
	
	/**
	 * export selected columns as sql format
	 * @return string
	 */
	public function exportSelectedColumns() {
		return ($this->selectAllColumns) ? $this->exportName.'.*' : implode(', ', $this->selectedColumns);
	}
	
	/**
	 *
	 * @return [string, [values]]
	 * - first element is the table exported in sql format
	 * - second emement is an array of exported values that need to be checked and replace in exported table
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