<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception\Manifest;

class SerializationManifestIdException extends ManifestException {
	
	private $type;
	private $id;
	
	/**
	 *
	 * @param string $type
	 * @param string|integer $id
	 */
	public function __construct($type, $id) {
		parent::__construct("impossible to load $type serialization with id '$id'");
		
		$this->type = $type;
		$this->id = $id;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * 
	 * @return string|integer
	 */
	public function getId() {
		return $this->id;
	}
	
}