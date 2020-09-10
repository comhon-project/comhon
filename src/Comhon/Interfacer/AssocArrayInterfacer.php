<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Interfacer;

use Comhon\Exception\ArgumentException;

class AssocArrayInterfacer extends Interfacer {

	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getMediaType()
	 */
	public function getMediaType() {
		return 'application/json';
	}
	
	/**
	 * get value in $node with key $name
	 * 
	 * @param array $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 * @return mixed|null null if doesn't exist
	 */
	public function &getValue(&$node, $name, $asNode = false) {
		if (array_key_exists($name, $node)) {
			return $node[$name];
		} else {
			// ugly but we return reference so we have to return a variable
			$null = null;
			return $null;
		}
	}
	
	/**
	 * verify if $node contain value with key $name
	 *
	 * @param array $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 * @return boolean
	 */
	public function hasValue($node, $name, $asNode = false) {
		return array_key_exists($name, $node);
	}
	
	/**
	 * verify if value is null
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNullValue($value) {
		return is_null($value);
	}
	
	/**
	 * get traversable node (return a copy of $node)
	 *
	 * @param array $node
	 * @return array
	 */
	public function getTraversableNode($node) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		return $node;
	}
	
	/**
	 * verify if value is an array
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNodeValue($value) {
		return is_array($value);
	}
	
	/**
	 * verify if value is an array
	 * 
	 * @param mixed $value
	 * @param boolean $isAssociative
	 * @return boolean
	 */
	public function isArrayNodeValue($value, $isAssociative) {
		return is_array($value);
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isComplexInterfacedId($value) {
		return is_array($value);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function isFlattenComplexInterfacedId($value) {
		return is_string($value) && substr($value, 0, 6) == '{"id":';
	}
	
	/**
	 * set value in $node with key $name
	 * 
	 * @param array $node
	 * @param mixed $value
	 * @param string $name must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		if (is_null($name)) {
			throw new ArgumentException($name, 'string', 3);
		}
		$node[$name] = $value;
	}
	
	/**
	 * unset value in $node with key $name
	 *
	 * @param array $node
	 * @param string $name
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 */
	public function unsetValue(&$node, $name, $asNode = false) {
		unset($node[$name]);
	}
	
	/**
	 * add value to $node
	 *
	 * @param array $node
	 * @param mixed $value
	 * @param string $name not used (but needed to stay compatible with interface)
	 */
	public function addValue(&$node, $value, $name = null) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		$node[] = $value;
	}
	
	/**
	 * add value to $node
	 *
	 * @param array $node
	 * @param mixed $value
	 * @param string $key
	 * @param string $name not used (there is a default value to stay compatible with interface)
	 */
	public function addAssociativeValue(&$node, $value, $key, $name = null) {
		if (!is_array($node)) {
			throw new ArgumentException($node, 'array', 1);
		}
		if (is_null($key)) {
			throw new ArgumentException($key, 'string', 3);
		}
		$node[$key] = $value;
	}
	
	/**
	 * create array node
	 * 
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return array
	 */
	public function createNode($name = null) {
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getNodeClasses()
	 */
	public function getNodeClasses() {
		return ['array'];
	}
	
	/**
	 * create array node
	 * 
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return array
	 */
	public function createArrayNode($name = null) {
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::getArrayNodeClasses()
	 */
	public function getArrayNodeClasses() {
		return ['array'];
	}
	
	/**
	 * transform given node to string
	 *
	 * @param array $node
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function toString($node, $prettyPrint = false) {
		return $prettyPrint ? json_encode($node, JSON_PRETTY_PRINT) : json_encode($node);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Interfacer\Interfacer::fromString()
	 */
	public function fromString($string) {
		return json_decode($string, true);
	}
	
	/**
	 * write file with given content
	 * 
	 * @param \stdClass $node
	 * @param string $path
	 * @param bool $prettyPrint
	 * @return boolean
	 */
	public function write($node, $path, $prettyPrint = false) {
		$data = $prettyPrint ? json_encode($node, JSON_PRETTY_PRINT) : json_encode($node);
		return file_put_contents($path, $data) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * 
	 * @param string $path
	 * @return array|null return null on failure
	 */
	public function read($path) {
		$json = file_get_contents($path);
		return $json ? json_decode($json, true) : null;
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * 
	 * @param array $node
	 * @param string $name
	 */
	public function flattenNode(&$node, $name) {
		if (array_key_exists($name, $node) && !is_null($node[$name])) {
			$node[$name] = json_encode($node[$name]);
		}
	}
	
	/**
	 * unflatten value (transform string to object/array)
	 * 
	 * @param array $node
	 * @param string $name
	 */
	public function unFlattenNode(&$node, $name) {
		if (array_key_exists($name, $node) && is_string($node[$name])) {
			$node[$name] = json_decode($node[$name], true);
		}
	}
	
	/**
	 * replace value in key $name by $value (fail if key $name doesn't exist)
	 * 
	 * @param array $node
	 * @param string $name
	 * @param mixed $value value to place in key $name
	 */
	public function replaceValue(&$node, $name, $value) {
		if (array_key_exists($name, $node)) {
			$this->setValue($node, $value, $name);
		}
	}
	
}
