<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Visitor;

use Comhon\Exception\ComhonException;

class VisitException extends ComhonException {
	
	/**
	 * @var \Exception
	 */
	private $originalException;
	
	/**
	 * @var array
	 */
	private $stackProperties = [];
	
	/**
	 * 
	 * @param \Exception $exception
	 * @param string[] $stackProperties
	 */
	public function __construct(\Exception $exception, $stackProperties) {
		$message = "Something goes wrong on '.".implode('.', $stackProperties)."' object : ".PHP_EOL.$exception->getMessage();
		$this->originalException = $exception;
		$this->stackProperties = $stackProperties;
		parent::__construct($message, $exception->getCode());
	}
	
	/**
	 * get original thrown exception
	 *
	 * @return \Comhon\Exception\ComhonException
	 */
	public function getOriginalException() {
		return $this->originalException;
	}
	
	/**
	 * get stack properties encountered during visit
	 *
	 * @return array
	 */
	public function getStackProperties() {
		return $this->stackProperties;
	}
	
	/**
	 * get stringified properties encountered during visit
	 *
	 * @return string
	 */
	public function getStringifiedProperties() {
		return '.'.implode('.', $this->stackProperties);
	}
}