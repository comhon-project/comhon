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

use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

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
	 * @var string
	 */
	private $content;
	
	public function setCode($code) {
		$this->code = $code;
	}
	
	public function getCode() {
		return $this->code;
	}
	
	public function addHeader($name, $value) {
		$this->headers[$name] = $value;
	}
	
	public function getHeaders() {
		return $this->headers;
	}
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function send() {
		http_response_code($this->code);
		foreach ($this->headers as $name => $value) {
			header("$name: $value");
		}
		if (!empty($this->content)) {
			echo $this->content;
		}
	}
	
}
