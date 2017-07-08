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

class OnLiteral extends DbLiteral {
	
	private $columnRight;
	private $tableRight;
	
	/**
	 * 
	 * @param TableNode|string $tableLeft
	 * @param string $columnLeft
	 * @param string $operator
	 * @param TableNode|string $tableRight
	 * @param string $columnRight
	 */
	public function __construct($tableLeft, $columnLeft, $operator, $tableRight, $columnRight) {
		parent::__construct($tableLeft, $columnLeft, $operator);
		$this->tableRight = $tableRight;
		$this->columnRight = $columnRight;
	}
	
	/**
	 * get right side column of join
	 * 
	 * @return string
	 */
	public function getColumnRight() {
		return $this->columnRight;
	}
	
	/**
	 * get right side table of join
	 * 
	 * @return TableNode|string
	 */
	public function getTableRight() {
		return $this->tableRight;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		$left = (($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column;
		$right = (($this->tableRight instanceof TableNode) ? $this->tableRight->getExportName() : $this->tableRight) . '.' . $this->columnRight;
		return sprintf('%s %s %s', $left, $this->operator, $right);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Formula::exportDebug()
	 */
	public function exportDebug() {
		$array = [];
		return $this->export($array);
	}
	
}