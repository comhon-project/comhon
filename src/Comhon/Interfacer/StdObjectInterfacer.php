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

class StdObjectInterfacer extends Interfacer {

	/**
	 *
	 * @param \stdClass $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return mixed|null
	 */
	public function &getValue(&$pNode, $pPropertyName, $pAsNode = false) {
		if (property_exists($pNode, $pPropertyName)) {
			return $pNode->$pPropertyName;
		} else {
			// ugly but we return reference so we have to return a variable
			$lNull = null;
			return $lNull;
		}
	}
	
	/**
	 *
	 * @param \stdClass $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return boolean
	 */
	public function hasValue($pNode, $pPropertyName, $pAsNode = false) {
		return property_exists($pNode, $pPropertyName);
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @return boolean
	 */
	public function isNullValue($pValue) {
		return is_null($pValue);
	}
	
	/**
	 *
	 * @param \stdClass $pNode
	 * @param boolean $pGetElementName not used
	 * @return array
	 */
	public function getTraversableNode($pNode, $pGetElementName = false) {
		if (!is_array($pNode)) {
			throw new \Exception('bad node type');
		}
		return $pNode;
	}
	
	/**
	 * verify if value is an stdClass object
	 * @param mixed $pValue
	 * @return boolean
	 */
	public function isNodeValue($pValue) {
		return ($pValue instanceof \stdClass);
	}
	
	/**
	 * verify if value is an array
	 * @param mixed $pValue
	 * @return boolean
	 */
	public function isArrayNodeValue($pValue) {
		return is_array($pValue);
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $pValue
	 * @return mixed
	 */
	public function isComplexInterfacedId($pValue) {
		return is_object($pValue);
	}
	
	/**
	 * verify if value is a flatten complex id (with inheritance key)
	 * @param mixed $pValue
	 * @return mixed
	 */
	public function isFlattenComplexInterfacedId($pValue) {
		return is_string($pValue) && substr($pValue, 0, 6) == '{"id":';
	}
	
	/**
	 * 
	 * @param \stdClass $pNode
	 * @param mixed $pValue
	 * @param string $pName must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $pAsNode not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function setValue(&$pNode, $pValue, $pName = null, $pAsNode = false) {
		if (!($pNode instanceof \stdClass)) {
			throw new \Exception('first parameter should be an instance of \stdClass');
		}
		if (is_null($pName)) {
			throw new \Exception('third parameter must be specified and not null');
		}
		$pNode->$pName = $pValue;
		return $pValue;
	}
	
	/**
	 *
	 * @param \stdClass $pNode
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	public function unsetValue(&$pNode, $pName, $pAsNode = false) {
		unset($pNode->$pName);
	}
	
	/**
	 *
	 * @param array $pNode
	 * @param mixed $pValue
	 * @param string $pName not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function addValue(&$pNode, $pValue, $pName = null) {
		if (!is_array($pNode)) {
			throw new \Exception('first parameter should be an array');
		}
		$pNode[] = $pValue;
	}
	
	/**
	 * @param string $pName not used (but needed to stay compatible with interface)
	 * return mixed
	 */
	public function createNode($pName = null) {
		return new \stdClass();
	}
	
	/**
	 * @param string $pName not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function createNodeArray($pName = null) {
		return [];
	}
    
	/**
	 * transform given node to string
	 * @param \stdClass $pNode
	 * @return string
	 */
	public function toString($pNode) {
		return json_encode($pNode);
	}
	
	/**
	 * write file with given content
	 * @param mixed $pNode
	 * @param string $pPath
	 * @return boolean
	 */
	public function write($pNode, $pPath) {
		return file_put_contents($pPath, json_encode($pNode)) !== false;
	}
	
	/**
	 * read file and load node with file content
	 * @param string $pPath
	 * @return \stdClass|boolean return false on failure
	 */
	public function read($pPath) {
		$lJson = file_get_contents($pPath);
		if (!$lJson) {
			return false;
		}
		return json_decode($lJson);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param \stdClass $pNode
	 * @param string $pName
	 */
	public function flattenNode(&$pNode, $pName) {
		if (isset($pNode->$pName)) {
			$pNode->$pName = json_encode($pNode->$pName);
		}
	}
	
	/**
	 * unflatten value (transform string to object/array)
	 * @param array $pNode
	 * @param string $pName
	 */
	public function unFlattenNode(&$pNode, $pName) {
		if (isset($pName, $pNode) && is_string($pNode->$pName)) {
			$pNode->$pName = json_decode($pNode->$pName);
		}
	}
	
	/**
	 * replace value
	 * @param \stdClass $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	public function replaceValue(&$pNode, $pName, $pValue) {
		if (property_exists($pNode, $pName)) {
			$this->setValue($pNode, $pValue, $pName);
		}
	}
	
}
