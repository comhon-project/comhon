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
 * a complex literal is like a literal but it value is a query
 * exemple : 
 * query => SELECT * FROM table WHERE column IN (SELECT column FROM table_2 WHERE column_2 = "a_value") AND column_3 = "a_value_2"
 * complex literal => column IN (SELECT column FROM table WHERE column_2 = "a_value")
 */
class ComplexLiteral extends WhereLiteral {

	const IN     = 'IN';
	const NOT_IN = 'NOT IN';
	
	protected static $acceptedOperators = [
			self::IN     => null,
			self::NOT_IN => null
	];
	
	protected static $oppositeOperator = [
			self::IN     => self::NOT_IN,
			self::NOT_IN => self::IN
	];
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->operator, self::$acceptedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (!is_null($this->value) && !($this->value instanceof SelectQuery)) {
			throw new \Exception('complex literal must have a query value');
		}
	}
	
	/**
	 * @param array $globalValues
	 * @return string
	 */
	public function export(&$globalValues) {
		list($query, $values) = $this->value->export();
		foreach ($values as $value) {
			$globalValues[] = $value;
		}
		return sprintf('%s.%s %s (%s)', $this->table, $this->column, $this->operator, $query);
	}
	
	/**
	 * can't be used to populate a database query
	 * @return string
	 */
	public function exportWithValue() {
		return sprintf('%s.%s %s (%s)', $this->table, $this->column, $this->operator, $this->value->exportWithValue());
	}
}