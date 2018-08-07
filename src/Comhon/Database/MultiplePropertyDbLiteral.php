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
use Comhon\Logic\Clause;
use Comhon\Model\Property\MultipleForeignProperty;

class MultiplePropertyDbLiteral extends Literal {
	
	/** @var array */
	protected static $allowedOperators = [
			self::EQUAL => null,
			self::DIFF  => null
	];
	
	/** @var \Comhon\Logic\Clause */
	private $clause;
	
	/**
	 * @param TableNode|string $table
	 * @param \Comhon\Model\Property\MultipleForeignProperty $property
	 * @param string $operator
	 * @param string|boolean|number|null|string[]|boolean[]|number[] $value
	 *     if $operator is self::EQUAL or self::DIFF you can specify an array of values
	 *     operator will be transformed respectively as sql operator IN or NOT IN.
	 *     array can have null value
	 * @throws \Exception
	 */
	public function __construct($table, MultipleForeignProperty $property, $operator, $value) {
		parent::__construct($operator);
		$this->value = $value;
		if (is_array($value)) {
			$this->clause = $operator === self::EQUAL ? new Clause(Clause::DISJUNCTION) : new Clause(Clause::CONJUNCTION);
			foreach ($value as $subValue) {
				$clause = $operator === self::EQUAL ? new Clause(Clause::CONJUNCTION) : new Clause(Clause::DISJUNCTION);
				$this->clause->addClause($clause);
				$this->addLiteral($clause, $table, $property, $operator, $subValue);
			}
		}
		else {
			$this->clause = $operator === self::EQUAL ? new Clause(Clause::CONJUNCTION) : new Clause(Clause::DISJUNCTION);
			$this->addLiteral($this->clause, $table, $property, $operator, $value);
		}
	}
	
	/**
	 * 
	 * @param \Comhon\Logic\Clause $clause
	 * @param string $table
	 * @param \Comhon\Model\Property\MultipleForeignProperty $property
	 * @param string $operator
	 * @param string $value
	 */
	private function addLiteral(Clause $clause, $table, MultipleForeignProperty $property, $operator, $value) {
		$idValues = $property->getUniqueModel()->decodeId($value);
		$i = 0;
		foreach ($property->getMultipleIdProperties() as $idPropertySerializationName => $idProperty) {
			$clause->addLiteral(new SimpleDbLiteral($table, $idPropertySerializationName, $operator, $idValues[$i]));
			$i++;
		}
	}

	/**
	 * export stringified literal to integrate it in sql query
	 *
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		return $this->clause->export($values);
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