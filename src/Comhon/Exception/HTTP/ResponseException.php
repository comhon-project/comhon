<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\HTTP;

use Comhon\Exception\ComhonException;
use Comhon\Api\ResponseBuilder;

class ResponseException extends ComhonException {
	
	/**
	 * 
	 * @var \Comhon\Api\Response
	 */
	private $response;
	
	/**
	 * 
	 * @param integer $code
	 * @param string|array|\stdClass $content
	 * @param string[] $headers
	 */
	public function __construct($statusCode, $content = null, $headers = []) {
		$this->response = ResponseBuilder::buildSimpleResponse($statusCode, $headers, $content);
	}
	
	/**
	 * 
	 * @return \Comhon\Api\Response
	 */
	public function getResponse() {
		return $this->response;
	}
	
}