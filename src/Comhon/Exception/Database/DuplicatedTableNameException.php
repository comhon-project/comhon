<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Database;

use Comhon\Exception\ConstantException;
use Comhon\Exception\ComhonException;

class DuplicatedTableNameException extends ComhonException {
	
	private $tableName;
	
	/**
	 * @param string $tableName table name that is duplicated in select query
	 */
	public function __construct($tableName) {
		parent::__construct("duplicated table '{$tableName}'", ConstantException::DUPLICATED_TABLE_NAME_EXCEPTION);
		$this->tableName = $tableName;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
}