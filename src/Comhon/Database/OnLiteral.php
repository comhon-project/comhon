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

class OnLiteral extends Literal {

	private $columnRight;
	
	public function __construct($tableLeft, $columnLeft, $operator, $tableRight, $columnRight) {
		$this->columnRight = $columnRight;
		parent::__construct($tableLeft, $columnLeft, $operator, $tableRight);
	}
	
	/**
	 * @return string
	 */
	public function getColumnRight() {
		return $this->columnRight;
	}
	
	/**
	 * 
	 * @return string|TableNode
	 */
	public function getTableRight() {
		return $this->value;
	}
	
	/**
	 * @param array $values
	 * @return string
	 */
	public function export(&$values) {
		$left = (($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column;
		$right = (($this->value instanceof TableNode) ? $this->value->getExportName() : $this->value) . '.' . $this->columnRight;
		return sprintf('%s %s %s', $left, $this->operator, $right);
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		throw new \Exception('cannot build OnLiteral from stdClass object');
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @param [] $leftJoins
	 * @param [] $literalCollection
	 * @throws \Exception
	 */
	public static function stdObjectToLiteral($stdObject, &$leftJoins, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		throw new \Exception('cannot build OnLiteral from stdClass object');
	}
	
}