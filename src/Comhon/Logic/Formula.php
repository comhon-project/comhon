<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Logic;

abstract class Formula {
	
	/**
	 * export stringified formula
	 *
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	abstract public function export(&$values);
	
	/**
	 * export stringified formula
	 * DO NOT USE this function to build a query that will be executed (it doesn't prevent from injection)
	 * USE this function for exemple for debug (it permit to see what query looks like)
	 *
	 * @return string
	 */
	abstract public function exportDebug();
	
}