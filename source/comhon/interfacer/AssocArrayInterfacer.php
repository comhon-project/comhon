<?php
namespace comhon\interfacer;

class AssocArrayInterfacer extends Interfacer {

	/**
	 *
	 * @param \stdClass $pNode
	 * @param string $pPropertyName
	 * @param boolean $pAsNode
	 * @return mixed|null
	 */
	public function &getValue(&$pNode, $pPropertyName, $pAsNode = false) {
		if (array_key_exists($pPropertyName, $pNode)) {
			return $pNode[$pPropertyName];
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
		return array_key_exists($pPropertyName, $pNode);
	}
	
	/**
	 *
	 * @param \stdClass $pNode
	 * @param boolean $pGetElementName not used
	 * @return array
	 */
	public function getTraversableNode($pNode, $pGetElementName = false) {
		return is_array($pNode) ? $pNode : [];
	}
	
	/**
	 * verify if value is a complex id (with inheritance key) or a simple value
	 * @param mixed $pValue
	 * @return mixed
	 */
	public function isComplexInterfacedId($pValue) {
		return is_array($pValue);
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
	 * @param array $pNode
	 * @param mixed $pValue
	 * @param string $pName must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $pAsNode not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function setValue(&$pNode, $pValue, $pName = null, $pAsNode = false) {
		if (!is_array($pNode)) {
			throw new \Exception('first parameter should be an instance of array');
		}
		if (is_null($pName)) {
			throw new \Exception('third parameter must be specified and not null');
		}
		$pNode[$pName] = $pValue;
		return $pValue;
	}
	
	/**
	 *
	 * @param array $pNode
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	public function deleteValue(&$pNode, $pName, $pAsNode = false) {
		unset($pNode[$pName]);
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
		return [];
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
	 * @param array $pNode
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
	 * @return array|boolean return false on failure
	 */
	public function read($pPath) {
		$lJson = file_get_contents($pPath);
		if (!$lJson) {
			return false;
		}
		return json_decode($lJson, true);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param array $pNode
	 * @param string $pName
	 */
	public function flattenNode(&$pNode, $pName) {
		if (array_key_exists($pName, $pNode) && !is_null($pNode[$pName])) {
			$pNode[$pName] = json_encode($pNode[$pName]);
		}
	}
	
	/**
	 * unflatten value (transform string to object/array)
	 * @param array $pNode
	 * @param string $pName
	 */
	public function unFlattenNode(&$pNode, $pName) {
		if (array_key_exists($pName, $pNode) && is_string($pNode[$pName])) {
			$pNode[$pName] = json_decode($pNode[$pName], true);
		}
	}
	
	/**
	 * replace value
	 * @param array $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	public function replaceValue(&$pNode, $pName, $pValue) {
		if (array_key_exists($pName, $pNode)) {
			$this->setValue($pNode, $pValue, $pName);
		}
	}
	
	/**
	 * verify if node is instance of stdClass
	 * @param mixed $pNode
	 * @return boolean
	 */
	public function verifyNode($pNode) {
		return is_array($pNode);
	}
	
}
