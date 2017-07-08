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

/**
 * a sub-query literal is like a simple literal but it value is a query
 * exemple : 
 * query => SELECT * FROM table WHERE column IN (SELECT column FROM table_2 WHERE column_2 = "a_value") AND column_3 = "a_value_2"
 * complex literal is => column IN (SELECT column FROM table WHERE column_2 = "a_value")
 */
class SubQueryLiteral extends DbLiteral {

	/** @var string */
	const IN = 'IN';
	
	/** @var string */
	const NOT_IN = 'NOT IN';
	
	/** @var SelectQuery */
	private $value;
	
	/** @var array */
	protected static $allowedOperators = [
			self::IN     => null,
			self::NOT_IN => null
	];
	
	/** @var array */
	protected static $oppositeOperator = [
			self::IN     => self::NOT_IN,
			self::NOT_IN => self::IN
	];
	
	/**
	 * @param TableNode|string $table
	 * @param string $column
	 * @param string $operator
	 * @param SelectQuery $value
	 * @throws \Exception
	 */
	public function __construct($table, $column, $operator, SelectQuery $value) {
		parent::__construct($table, $column, $operator);
		$this->value = $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Literal::export()
	 */
	public function export(&$globalValues) {
		list($query, $values) = $this->value->export();
		foreach ($values as $value) {
			$globalValues[] = $value;
		}
		return sprintf('%s.%s %s (%s)', $this->table, $this->column, $this->operator, $query);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Literal::exportDebug()
	 */
	public function exportDebug() {
		return sprintf('%s.%s %s (%s)', $this->table, $this->column, $this->operator, $this->value->exportDebug());
	}
}