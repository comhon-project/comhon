<?php
namespace comhon\interfacer;

interface Interfacer {

	/**
	 * 
	 * @param mixed $pNode
	 * @param mixed $pValue
	 */
	public function setValue($pNode, $pName, $pValue);
	
	/**
	 * 
	 * @param mixed $pNodeArray
	 * @param mixed $pValue
	 * @param string $pNodeNameElement
	 */
	public function addValue(&$pNodeArray, $pValue, $pNodeNameElement = null);

	/**
	 * return mixed node
	 */
	public function createNode();
	
	/**
	 * @return mixed node array
	 */
	public function createNodeArray();
    
}
