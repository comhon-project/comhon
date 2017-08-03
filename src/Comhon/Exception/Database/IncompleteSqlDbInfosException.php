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
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ComhonException;

class IncompleteSqlDbInfosException extends ComhonException {
	
	public function __construct() {
		$message = "missing required values for database connection. required values : "
				.json_encode(ModelManager::getInstance()->getInstanceModel('sqlDatabase')->getPropertiesNames());
		parent::__construct($message, ConstantException::INCOMPLETE_SQL_DB_INFOS_EXCEPTION);
	}
	
}