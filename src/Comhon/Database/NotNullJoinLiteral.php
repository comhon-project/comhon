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

	private $conjunction;
	
	public function __construct() {
		$this->conjunction = new Conjunction();
	}
	
	public function addLiteral($table, $column) {
		$this->conjunction->addLiteral(new Literal($table, $column, Literal::DIFF, null));
	}
	
	
	/**
	 * @param array $values
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
	 * 
	 * @param \stdClass $stdObject
	 * @param [] $leftJoins
	 * @param [] $literalCollection
	 * @throws \Exception
	 */
	public static function stdObjectToLiteral($stdObject, &$leftJoins, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
}