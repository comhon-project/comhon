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

class NotSupportedDBMSException extends ComhonException {
	
	/**
	 * @param string $DBMS
	 */
	public function __construct($DBMS) {
		parent::__construct("Database management system '$DBMS' not supported", ConstantException::NOT_SUPPORTED_DBMS_EXCEPTION);
	}
	
}