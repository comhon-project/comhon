<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Serialization;

use Comhon\Exception\ComhonException;
use Comhon\Exception\ConstantException;

class ManifestSerializationException extends ComhonException {
	
	/** @var integer */
	private $httpCode;
	
	/**
	 *
	 * @param string $message
	 * @param integer $httpCode
	 */
	public function __construct($message, $httpCode = 400) {
		parent::__construct($message, ConstantException::MANIFEST_SERIALIZATION_EXCEPTION);
		$this->httpCode = $httpCode;
	}
	
	/**
	 *
	 * @return number
	 */
	public function getHttpCode() {
		return $this->httpCode;
	}
	
}