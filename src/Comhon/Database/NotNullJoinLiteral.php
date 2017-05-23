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

	private $mConjunction;
	
	public function __construct() {
		$this->mConjunction = new Conjunction();
	}
	
	public function addLiteral($pTable, $pColumn) {
		$this->mConjunction->addLiteral(new Literal($pTable, $pColumn, Literal::DIFF, null));
	}
	
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		return $this->mConjunction->export($pValues);
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($pStdObject) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
	/**
	 * 
	 * @param \stdClass $pStdObject
	 * @param [] $pLeftJoins
	 * @param [] $pLiteralCollection
	 * @throws \Exception
	 */
	public static function stdObjectToLiteral($pStdObject, &$pLeftJoins, $pLiteralCollection = null, $pSelectQuery = null, $pAllowPrivateProperties = true) {
		throw new \Exception('cannot build NotNullLiteral from stdClass object');
	}
	
}