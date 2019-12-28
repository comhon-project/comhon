<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\Comhon\Mock;

use Comhon\Api\RequestHandler;

class RequestHandlerMock extends RequestHandler {
	
	public static function handle($basePath, $server = [], $get = [], $headers = []) {
		$handler = new self();
		return $handler->_handle($basePath, $server, $get, $headers);
	}
	
}