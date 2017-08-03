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

class UnexpectedCountValuesQueryException extends ComhonException {
	
	/**
	 * @param string $query
	 * @param integer $expectedCountValues
	 * @param integer $actualCountValues
	 */
	public function __construct($query, $expectedCountValues, $actualCountValues) {
		$message = "\n\npreparation query failed :\n'"
					. $query
					."'\nerrorInfo : \n"
							. "expect $expectedCountValues values, $actualCountValues values given."
							."'\n\n";
		parent::__construct($message, ConstantException::UNEXPECTED_COUNT_VALUES_QUERY_EXCEPTION);
	}
	
}