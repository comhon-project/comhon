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
use Comhon\Api\Response;

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
	public function __construct($code, $content = null, $headers = []) {
		$this->response = new Response();
		$this->response->setCode($code);
		foreach ($headers as $name => $value) {
			$this->response->addHeader($name, $value);
		}
		$this->response->setContent($content);
	}
	
	/**
	 * 
	 * @return \Comhon\Api\Response
	 */
	public function getResponse() {
		return $this->response;
	}
	
}