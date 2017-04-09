<?php
namespace comhon\interfacer;

class ArrayInterfacer extends Interfacer {

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
	 * serialize given node
	 * @param array $pNode
	 * @return string
	 */
	public function serialize($pNode) {
		return json_encode($pNode);
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
	protected function _verifyNode($pNode) {
		return is_array($pNode);
	}
	
}
