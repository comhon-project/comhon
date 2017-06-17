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

class NotNullJoinLiteral extends Literal {

	/** @var Conjunction */
	private $conjunction;
	
	public function __construct() {
		$this->conjunction = new Conjunction();
	}
	
	/**
	 * 
	 * @param TableNode|string $table
	 * @param string $column
	 */
	public function addLiteral($table, $column) {
		$this->conjunction->addLiteral(new Literal($table, $column, Literal::DIFF, null));
	}
	
	
	/**
	 * export stringified literal to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		return $this->conjunction->export($values);
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
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
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
}