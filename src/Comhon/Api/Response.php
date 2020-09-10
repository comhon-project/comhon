<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Api;

use GuzzleHttp\Psr7;

class Response extends Psr7\Response {
	
	/**
	 * send HTTP response (status code, headers and body)
	 */
	public function send() {
		http_response_code($this->getStatusCode());
		foreach ($this->getHeaders() as $header => $value) {
			header("$header: ".$this->getHeaderLine($header));
		}
		if (!is_null($this->getBody())) {
			echo $this->getBody()->getContents();
		}
	}
	
	/**
	 * get body content from the beginning to the end. 
	 * stream pointer stay at same position.
	 * 
	 * @return string
	 */
	public function getFullBodyContents() {
		$offset = $this->getBody()->tell();
		$this->getBody()->rewind();
		$body = $this->getBody()->getContents();
		$this->getBody()->seek($offset);
		
		return $body;
	}
	
}
