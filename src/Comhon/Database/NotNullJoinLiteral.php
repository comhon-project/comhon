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

use Comhon\Logic\Clause;
use Comhon\Logic\Literal;

class NotNullJoinLiteral extends DbLiteral {

	/** @var Conjunction */
	private $conjunction;
	
	public function __construct() {
		$this->conjunction = new Clause(Clause::CONJUNCTION);
	}
	
	/**
	 * add literal
	 * 
	 * @param TableNode|string $table
	 * @param string $column
	 */
	public function addLiteral($table, $column) {
		$this->conjunction->addLiteral(new SimpleDbLiteral($table, $column, Literal::DIFF, null));
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
	 * {@inheritDoc}
	 * @see \Comhon\Logic\Formula::exportDebug()
	 */
	public function exportDebug() {
		$array = [];
		return $this->export($array);
	}
	
}