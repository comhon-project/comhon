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

use Comhon\Logic\Literal;
use Comhon\Exception\ComhonException;

class SimpleDbLiteral extends DbLiteral {
	
	/** @var string|boolean|number|null|string[]|boolean[]|number[] */
	private $value;
	
	/**
	 * @param TableNode|string $table
	 * @param string $column 
	 * @param string $operator
	 * @param string|boolean|number|null|string[]|boolean[]|number[] $value 
	 *     if $operator is self::EQUAL or self::DIFF you can specify an array of values 
	 *     operator will be transformed respectively as sql operator IN or NOT IN.
	 *     array can have null value
	 * @throws \Exception
	 */
	public function __construct($table, $column, $operator, $value) {
		parent::__construct($table, $column, $operator);
		$this->value = $value;
		$this->_verifLiteral();
	}
	
	/**
	 * verify if literal has expected format
	 * 
	 * @throws \Exception
	 */
	protected function _verifLiteral() {
		if (is_null($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new ComhonException('literal with operator \''.$this->operator.'\' can\'t have null value');
		}
		if (is_array($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new ComhonException('literal with operator \''.$this->operator.'\' can\'t have array value');
		}
	}

	/**
	 * @return string|number
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @param string|number $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * @return boolean
	 */
	public function hasId() {
		return !is_null($this->id);
	}
	
	/**
	 * 
	 * @return TableNode|string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * @return string
	 */
	public function getPropertyName() {
		return $this->column;
	}
	
	/**
	 * @return string
	 */
	public function getOperator() {
		return $this->operator;
	}
	
	/**
	 * reverse operator
	 * 
	 * exemple :
	 * self::EQUAL become self::DIFF
	 * self::INF become self::SUPP_EQUAL
	 */
	public function reverseOperator() {
		$this->operator = self::$oppositeOperator[$this->operator];
	}
	
	/**
	 * 
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		$columnTable = (($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column;
		if ((($this->operator == '=') || ($this->operator == '<>')) && is_array($this->value)) {
			$i = 0;
			$toReplaceValues = [];
			$hasNullValue = false;
			while ($i < count($this->value)) {
				if (is_null($this->value[$i])) {
					$hasNullValue = true;
				}else {
					$values[] = $this->value[$i];
					$toReplaceValues[] = '?';
				}
				$i++;
			}
			$operator = ($this->operator == '=') ? ' IN ' : ' NOT IN ';
			$toReplaceValues = '('.implode(',', $toReplaceValues).')';
			$stringValue = sprintf('%s %s %s', $columnTable, $operator, $toReplaceValues);
			if ($hasNullValue) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$connector = ($this->operator == '=') ? 'or' : 'and';
				$stringValue = sprintf('(%s %s %s %s)', $stringValue, $connector, $columnTable, $operator);
			}
		}else {
			if (is_null($this->value)) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$stringValue = sprintf('%s %s', $columnTable, $operator);
			}else {
				$values[] = $this->value;
				$stringValue = sprintf('%s %s ?', $columnTable, $this->operator);
			}
		}
		return $stringValue;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * DO NOT USE this function to build a query that will be executed (it doesn't prevent from injection)
	 * USE this function for exemple for debug (it permit to see what query looks like)
	 *
	 * @return string
	 */
	public function exportDebug() {
		if ((($this->operator == '=') || ($this->operator == '<>')) && is_array($this->value)) {
			$i = 0;
			$toReplaceValues = [];
			$hasNullValue = false;
			while ($i < count($this->value)) {
				if (is_null($this->value[$i])) {
					$hasNullValue = true;
				}else {
					$toReplaceValues[] = $this->value[$i];
				}
				$i++;
			}
			$operator = ($this->operator == '=') ? ' IN ' : ' NOT IN ';
			$toReplaceValues = '('.implode(',', $toReplaceValues).')';
			$stringValue = sprintf('%s.%s %s %s', $this->table, $this->column, $operator, $toReplaceValues);
			if ($hasNullValue) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$connector = ($this->operator == '=') ? 'or' : 'and';
				$stringValue = sprintf('(%s %s %s.%s %s)', $stringValue, $connector, $this->table, $this->column, $operator);
			}
		}else {
			if (is_null($this->value)) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$stringValue = sprintf('%s.%s %s', $this->table, $this->column, $operator);
			}else {
				$stringValue = sprintf('%s.%s %s %s', $this->table, $this->column, $this->operator, $this->value);
			}
		}
		return $stringValue;
	}
	
}