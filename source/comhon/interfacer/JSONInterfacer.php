<?php
namespace comhon\interfacer;

class JSONInterfacer implements Interfacer {

	private $mStdObject;
	
	/**
	 * initialize stdClass object that may be serialized later in json format
	 * @param string $pRootName not used (but needed to stay compatible with interface)
	 * @throws \Exception
	 * @return \stdClass
	 */
	public function initialize($pRootName = null) {
		$this->mStdObject = new \stdClass();
		return $this->mStdObject;
	}
	
	/**
	 * 
	 * @param \stdClass $pNode
	 * @param mixed $pValue
	 * @param string $pName must be specified and not null (there is a default value to stay compatible with interface)
	 * @param boolean $pAsNode not used (but needed to stay compatible with interface)
	 * @return mixed
	 */
	public function setValue($pNode, $pValue, $pName = null, $pAsNode = false) {
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
	 * serialize stdClass object previously initialized
	 * @return string
	 */
	public function serialize() {
		return json_encode($this->mStdObject);
	}
	
}
