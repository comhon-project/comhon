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
	
	/**
	 * 
	 * @param TableNode|string $tableLeft
	 * @param string $columnLeft
	 * @param string $operator
	 * @param TableNode|string $tableRight
	 * @param string $columnRight
	 */
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
	 * @return TableNode|string
	 */
	public function getTableRight() {
		return $this->value;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
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
	 * @param \stdClass $stdObject
	 * @param \Comhon\Model\MainModel $mainModel
	 * @param Literal[] $literalCollection used if $stdObject contain only an id that reference literal in collection
	 * @param SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToLiteral($stdObject, $mainModel, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		throw new \Exception('cannot build OnLiteral from stdClass object');
	}
	
}