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

class QueryExecutionFailureException extends ComhonException {
	
	/**
	 * 
	 * @var \PDOStatement
	 */
	private $PDOStatement;
	
	/**
	 * @param \PDOStatement|string $query
	 */
	public function __construct($query) {
		$message = "\n\nexecution query failed :\n'";
		if ($query instanceof \PDOStatement) {
			$this->PDOStatement = $query;
			$message .= $query->queryString
					."'\n\nPDO errorInfo : \n"
							.var_export($query->errorInfo(), true)
							."'\n";
		} else {
			$message .= $query;
		}
		parent::__construct($message, ConstantException::QUERY_EXECUTION_FAILURE_EXCEPTION);
	}
	
	public function getPDOStatement() {
		return $this->PDOStatement;
	}
	
}