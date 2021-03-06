<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Interfacer;

use Comhon\Exception\ComhonException;

class InterfaceException extends ComhonException {
	
	/**
	 * @var \Comhon\Exception\ComhonException
	 */
	private $originalException;
	
	/**
	 * @var array
	 */
	private $stackProperties = [];
	
	/**
	 * 
	 * @param \Comhon\Exception\ComhonException $exception
	 * @param string $property
	 */
	public function __construct(ComhonException $exception, $property = null) {
		if ($exception instanceof InterfaceException) {
			$this->stackProperties = $exception->getStackProperties();
			$this->originalException = $exception->getOriginalException();
		} else {
			$this->originalException = $exception;
		}
		if (!is_null($property)) {
			$this->stackProperties[] = $property;
		}
		$message = "Something goes wrong on '{$this->getStringifiedProperties()}' value : ".PHP_EOL
			.$this->originalException->getMessage();
		parent::__construct($message, $this->originalException->getCode());
	}
	
	/**
	 * get InterfaceException instance with specified properies
	 * stack must begin (index 0) by the last encountered property,
	 * stack must end with the first encountered property
	 * 
	 * @param \Comhon\Exception\ComhonException $exception
	 * @param string[] $stackProperties
	 */
	public static function getInstanceWithProperties(ComhonException $exception, array $stackProperties) {
		$e = new InterfaceException($exception);
		$e->stackProperties = $stackProperties;
		return $e;
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
	 * get stack properties encountered during interface (export/import)
	 * 
	 * stack begin (index 0) by the last encountered property,
	 * stack end with the first encountered property
	 * 
	 * @return array
	 */
	public function getStackProperties() {
		return $this->stackProperties;
	}
	
	/**
	 * get stringified properties encountered during interface (export/import)
	 *
	 * @return string
	 */
	public function getStringifiedProperties() {
		return '.'.implode('.', array_reverse($this->stackProperties));
	}
	
}