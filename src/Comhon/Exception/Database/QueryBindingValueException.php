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

class QueryBindingValueException extends ComhonException {
	
	/**
	 * @param \PDOStatement $PDOStatement
	 */
	public function __construct($PDOStatement) {
		$message = "\n\nbinding value query failed :\n'"
				.$PDOStatement->queryString
				."'\n\nPDO errorInfo : \n"
						.var_export($PDOStatement->errorInfo(), true)
						."'\n";
		parent::__construct($message, ConstantException::QUERY_BINDING_VALUE_FAILURE_EXCEPTION);
	}
	
}