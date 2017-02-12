<?php
namespace comhon\database;

class TableNode {

	private $mTable;
	private $mAlias;
	private $mExportName;
	private $mSelectedColumns = [];
	private $mSelectAllColumns = true;
	
	/**
	 * 
	 * @param string|SelectQuery $pTable
	 * @param string $pAlias
	 */
	public function __construct($pTable, $pAlias = null, $pSelectAllColumns = true) {
		if (($pTable instanceof SelectQuery) && is_null($pAlias)) {
			throw new \Exception('TableNode must have an alias if specified table is an instance of SelectQuery');
		}
		$this->mTable = $pTable;
		$this->mAlias = $pAlias;
		$this->mExportName = is_null($this->mAlias) ? $this->mTable : $this->mAlias;
		$this->mSelectAllColumns = $pSelectAllColumns;
	}
	
	/**
	 * 
	 * @return string return table name or alias if exists
	 */
	public function getTable() {
		return $this->mTable;
	}
	
	/**
	 *
	 * @return string return table name or alias if exists
	 */
	public function getExportName() {
		return $this->mExportName;
	}
	
	/**
	 * add selected column
	 * if export all columns has been set to true, it is automaticaly set to false
	 * @param string $pColumn
	 * @param string $pAlias
	 * @return TableNode
	 */
	public function addSelectedColumn($pColumn, $pAlias = null) {
		$this->mSelectAllColumns = false;
		$this->mSelectedColumns[] = is_null($pAlias) 
			? $this->mExportName . '.' . $pColumn : $this->mExportName . '.' . $pColumn . ' AS ' . $pAlias;
		return $this;
	}
	
	/**
	 * all selected columns previously set will be reset
	 * @return TableNode
	 */
	public function resetSelectedColumns() {
		$this->mSelectedColumns = [];
		return $this;
	}
	
	/**
	 * determine if all columns will be selected or not
	 * @param boolean $pColumn if true, all selected columns previously set will be reset
	 * @return TableNode
	 */
	public function selectAllColumns($pBoolean) {
		if ($pBoolean) {
			$this->mSelectedColumns = [];
		}
		$this->mSelectAllColumns = $pBoolean;
		return $this;
	}
	
	/**
	 * verify if all columns will be exported
	 * @return boolean
	 */
	public function areAllColumnsSelected() {
		return $this->mSelectAllColumns;
	}
	
	/**
	 * verify if all columns will be exported
	 * @return boolean
	 */
	public function hasSelectedColumns() {
		return $this->mSelectAllColumns || !empty($this->mSelectedColumns);
	}
	
	/**
	 * export selected columns as sql format
	 * @return string
	 */
	public function exportSelectedColumns() {
		return ($this->mSelectAllColumns) ? $this->mExportName.'.*' : implode(', ', $this->mSelectedColumns);
	}
	
	/**
	 *
	 * @return [string, [values]]
	 * - first element is the table exported in sql format
	 * - second emement is an array of exported values that need to be checked and replace in exported table
	 */
	public function exportTable() {
		if ($this->mTable instanceof SelectQuery) {
			list($lSelectQuery, $lValues) = $this->mTable->export();
			$lExport = " ($lSelectQuery) AS $this->mAlias";
		} else {
			$lValues = [];
			$lExport = is_null($this->mAlias) ? $this->mTable : $this->mTable . ' AS ' . $this->mAlias;
		}
		return [$lExport, $lValues];
	}
}