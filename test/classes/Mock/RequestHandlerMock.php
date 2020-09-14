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
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

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
	public static function handle($basePath, $server = [], $get = [], $headers = [], $body = '', array $requestableModels = null) {
		$handler = new self();
		
		$uri = new Uri('http://localhost/'.$server['REQUEST_URI']);
		$serverRequest = new ServerRequest($server['REQUEST_METHOD'], $uri, $headers, $body);
		$serverRequest = $serverRequest->withQueryParams($get);
		$resolver = is_null($requestableModels) ? null : function ($pathModelName) use ($requestableModels) {
			$key = strtolower($pathModelName);
			return array_key_exists($key, $requestableModels) ? $requestableModels[$key] : null;
		};
		
		return $handler->_handle($serverRequest, $basePath, $resolver);
	}
	
}