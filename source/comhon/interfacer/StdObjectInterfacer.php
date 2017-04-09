<?php
namespace comhon\interfacer;

class StdObjectInterfacer extends Interfacer {

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
	 * serialize given node
	 * @param \stdClass $pNode
	 * @return string
	 */
	public function serialize($pNode) {
		return json_encode($pNode);
	}
	
	/**
	 * flatten value (transform object/array to string)
	 * @param \stdClass $pNode
	 * @param string $pName
	 */
	public function flattenNode(&$pNode, $pName) {
		if (isset($pNode->$pName) && !is_null($pNode->$pName)) {
			$pNode->$pName = json_encode($pNode->$pName);
		}
	}
	
	/**
	 * replace value
	 * @param \stdClass $pNode
	 * @param string $pName
	 * @param mixed $pValue
	 */
	public function replaceValue(&$pNode, $pName, $pValue) {
		if (isset($pNode->$pName)) {
			$this->setValue($pNode, $pValue, $pName);
		}
	}
	
	/**
	 * verify if node is instance of stdClass
	 * @param mixed $pNode
	 * @return boolean
	 */
	protected function _verifyNode($pNode) {
		return ($pNode instanceof \stdClass);
	}
	
}
