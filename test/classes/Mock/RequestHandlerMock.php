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
	
	/**
	 * 
	 * @param string $basePath
	 * @param string[] $server
	 * @param string[] $get
	 * @param string[] $headers
	 * @param string $body
	 * @param string[] $RequestableModels
	 * @return \Comhon\Api\Response
	 */
	public static function handle($basePath, $server = [], $get = [], $headers = [], $body = '', array $RequestableModels = null) {
		$handler = new self();
		return $handler->_handle($basePath, $RequestableModels, $server, $get, $headers, $body);
	}
	
}