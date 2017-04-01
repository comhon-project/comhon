<?php
namespace comhon\interfacer;

class XMLInterfacer implements Interfacer {

	private $mDomDocument;
	
	public function initialize($pRootName = null) {
		if (is_null($pRootName)) {
			throw new \Exception('interfacer initialization error : missing root name');
		}
		$this->mDomDocument = new \DOMDocument();
		return $this->mDomDocument->appendChild($this->mDomDocument->createElement($pRootName));
	}
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param mixed $pValue
	 */
	public function setValue($pNode, $pName, $pValue, $pAsNode = true) {
		if ($pAsNode) {
			if ($pValue instanceof \DOMNode) {
				
			}
		}
	}
	
	/**
	 * 
	 * @param mixed $pNodeArray
	 * @param mixed $pValue
	 * @param string $pNodeNameElement
	 */
	public function addValue(&$pNodeArray, $pValue, $pNodeNameElement = null) {
		
	}

	/**
	 * return mixed node
	 */
	public function addNode($pNode, $pName = null) {
	
	}
	
	/**
	 * return mixed node
	 */
	public function createNode($pName = null) {
		
	}
	
	/**
	 * @return mixed node array
	 */
	public function createNodeArray() {
		
	}
    
}
