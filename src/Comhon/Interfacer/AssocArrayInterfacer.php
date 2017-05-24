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

class AssocArrayInterfacer extends Interfacer {

	/**
	 *
	 * @param \stdClass $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return mixed|null
	 */
	public function &getValue(&$node, $propertyName, $asNode = false) {
		if (array_key_exists($propertyName, $node)) {
			return $node[$propertyName];
		} else {
			// ugly but we return reference so we have to return a variable
			$null = null;
			return $null;
		}
	}
	
	/**
	 *
	 * @param \stdClass $node
	 * @param string $propertyName
	 * @param boolean $asNode
	 * @return boolean
	 */
	public function hasValue($node, $propertyName, $asNode = false) {
		return array_key_exists($propertyName, $node);
	}
	
	/**
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNullValue($value) {
		return is_null($value);
	}
	
	/**
	 *
	 * @param \stdClass $node
	 * @param boolean $getElementName not used
	 * @return array
	 */
	public function getTraversableNode($node, $getElementName = false) {
		if (!is_array($node)) {
			throw new \Exception('bad node type');
		}
		return $node;
	}
	
	/**
	 * verify if value is an array
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNodeValue($value) {
		return is_array($value);
	}
	
	/**
	 * verify if value is an array
	 * @param mixed $value
	 * @return boolean
	 */
	public function isArrayNodeValue($value) {
		return is_array($value);
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $value
	 * @return mixed
	 */
	public function isComplexInterfacedId($value) {
		return is_array($value);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * @param mixed $value
	 * @return mixed
	 */
	public function isFlattenComplexInterfacedId($value) {
		return is_string($value) && substr($value, 0, 6) == '{"id":';
	}
	
	/**
	 * 
	 * @param array $node
	 * @param mixed $value
	 * @param string $name must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $asNode not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function setValue(&$node, $value, $name = null, $asNode = false) {
		if (!is_array($node)) {
			throw new \Exception('first parameter should be an instance of array');
		}
		if (is_null($name)) {
			throw new \Exception('third parameter must be specified and not null');
		}
		$node[$name] = $value;
		return $value;
	}
	
	/**
	 *
	 * @param array $node
	 * @param string $name
	 * @param boolean $asNode
	 * @return mixed
	 */
	public function unsetValue(&$node, $name, $asNode = false) {
		unset($node[$name]);
	}
	
	/**
	 *
	 * @param array $node
	 * @param mixed $value
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function addValue(&$node, $value, $name = null) {
		if (!is_array($node)) {
			throw new \Exception('first parameter should be an array');
		}
		$node[] = $value;
	}
	
	/**
	 * @param string $name not used (but needed to stay compatible with interface)
	 * return mixed
	 */
	public function createNode($name = null) {
		return [];
	}
	
	/**
	 * @param string $name not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function createNodeArray($name = null) {
		return [];
	}
    
	/**
	 * transform given node to string
	 * @param array $node
	 * @return string
	 */
	public function toString($node) {
		return json_encode($node);
	}
	
	/**
	 * write file with given content
	 * @param mixed $node
	 * @param string $path
	 * @return boolean
	 */
	public function write($node, $path) {
		return file_put_contents($path, json_encode($node)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * @param string $path
	 * @return array|boolean return false on failure
	 */
	public function read($path) {
		$json = file_get_contents($path);
		if (!$json) {
			return false;
		}
		return json_decode($json, true);
	}
	
	/**
	 * flatten value (transform object/array to string)
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
	 * @param array $node
	 * @param string $name
	 */
	public function unFlattenNode(&$node, $name) {
		if (array_key_exists($name, $node) && is_string($node[$name])) {
			$node[$name] = json_decode($node[$name], true);
		}
	}
	
	/**
	 * replace value
	 * @param array $node
	 * @param string $name
	 * @param mixed $value
	 */
	public function replaceValue(&$node, $name, $value) {
		if (array_key_exists($name, $node)) {
			$this->setValue($node, $value, $name);
		}
	}
	
}
