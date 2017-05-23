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

class Conjunction extends LogicalJunction {
	
	public function __construct() {
		parent::__construct(LogicalJunction::CONJUNCTION);
	}
	
}