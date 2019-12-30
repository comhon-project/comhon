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

class Response {
	
	/**
	 * 
	 * @var integer
	 */
	private $code = 200;
	
	/**
	 *
	 * @var string[]
	 */
	private $headers = [];
	
	/**
	 * 
	 * @var string|array|\stdClass
	 */
	private $content;
	
	/**
	 * set HTTP code
	 * 
	 * @param integer $code
	 */
	public function setCode($code) {
		$this->code = $code;
	}
	
	/**
	 * get HTTP code
	 * 
	 * @return integer
	 */
	public function getCode() {
		return $this->code;
	}
	
	/**
	 * add HTTP header
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function addHeader($name, $value) {
		$this->headers[$name] = $value;
	}
	
	/**
	 * get HTTP headers
	 * 
	 * @return string[]
	 */
	public function getHeaders() {
		return $this->headers;
	}
	
	/**
	 * set HTTP body content
	 * 
	 * @param string|array|\stdClass $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * get HTTP body content
	 * 
	 * @return string|array|\stdClass
	 */
	public function getContent() {
		return $this->content;
	}
	
	/**
	 * send HTTP response (code, headers and body content)
	 */
	public function send() {
		list($code, $headers, $content) = $this->getSend();
		http_response_code($code);
		foreach ($headers as $name => $value) {
			header("$name: $value");
		}
		if (!is_null($content)) {
			echo $content;
		}
	}
	
	/**
	 * get all informations that will be sent if you call function send().
	 * 
	 * @return [integer, string[], string] code, headers, body content
	 */
	public function getSend() {
		$headers = $this->headers;
		$content = null;
		
		if (!empty($this->content)) {
			$content = $this->content;
			if (($this->content instanceof \stdClass) || is_array($this->content)) {
				$content = json_encode($this->content);
				$headers['Content-Type'] = 'application/json';
			} elseif (is_string($this->content) && !array_key_exists('Content-Type', $headers)) {
				$headers['Content-Type'] = 'text/plain';
			}
		}
		return [$this->code, $headers, $content];
	}
	
}
