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

	/** @var string */
	const IN = 'IN';
	
	/** @var string */
	const NOT_IN = 'NOT IN';
	
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Database\Literal::_verifLiteral()
	 */
	protected function _verifLiteral() {
		if (!array_key_exists($this->operator, self::$allowedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (!is_null($this->value) && !($this->value instanceof SelectQuery)) {
			throw new \Exception('complex literal must have a query value');
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Database\Literal::export()
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
	 * @see \Comhon\Database\Literal::exportWithValue()
	 */
	public function exportWithValue() {
		return sprintf('%s.%s %s (%s)', $this->table, $this->column, $this->operator, $this->value->exportDebug());
	}
}