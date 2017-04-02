<?php
namespace comhon\interfacer;

interface Interfacer {

	/**
	 * initialize object that may be serialized later
	 * @param string $pRootName
	 * @throws \Exception
	 * @return mixed
	 */
	public function initialize($pRootName = null);
	
	/**
	 * 
	 * @param mixed $pNode
	 * @param mixed $pValue
	 * @param string $pName
	 * @param boolean $pAsNode
	 * @return mixed
	 */
	public function setValue($pNode, $pValue, $pName = null, $pAsNode = false);
	
	/**
	 *
	 * @param mixed $pNode
	 * @param mixed $pValue
	 * @param string $pName
	 * @return mixed
	 */
	public function addValue(&$pNode, $pValue, $pName = null);
	
	/**
	 * @param string $pName
	 * return mixed
	 */
	public function createNode($pName = null);
	
	/**
	 * @param string $pName
	 * @return mixed
	 */
	public function createNodeArray($pName = null);
	
	/**
	 * serialize object previously initialized
	 * @return string
	 */
	public function serialize();
    
}
