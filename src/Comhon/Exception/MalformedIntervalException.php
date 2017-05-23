<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception;

class MalformedIntervalException extends \Exception {
	
	public function __construct($pInterval) {
		parent::__construct("interval '$pInterval' not valid", ConstantException::MALFORMED_INTERVAL_EXCEPTION);
	}
	
}