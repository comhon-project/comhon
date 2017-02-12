<?php
namespace comhon\utils\structure;

class Node {
	
	private $mValue;
	private $mPreviousNode;
	private $mPeviousEdge;
	private $mNextNodes;
	private $mNextEdges;
	
	public function __construct($pValue, $pPreviousNode = null, $pNextNodes = []) {
		$this->mValue = $pValue;
		$this->mPreviousNode = $pPreviousNode;
		$this->mNextNodes = $pNextNodes;
	}
	
	public function getValue() {
		return $this->mValue;
	}
	
	public function getPrevious() {
		return $this->mPreviousNode;
	}
	
	public function getParent() {
		return $this->getPrevious();
	}
	
	public function setPrevious($pNode) {
		if (is_null($pNode) || (is_object($pNode) && ($pNode instanceof Node))) {
			$this->mPreviousNode = $pNode;
		}
	}
	
	public function setParent($pNode) {
		$this->setPrevious($pNode);
	}
	
	public function getNext() {
		return $this->mNextNodes[0];
	}
	
	public function setNext($pNode) {
		if (is_null($pNode) || (is_object($pNode) && ($pNode instanceof Node))) {
			$this->mNextNodes = [$pNode];
		}
	}
	
	public function getNeighbors() {
		return $this->mNextNodes;
	}
	
	public function pushNeighbor($pNode) {
		if (is_object($pNode) && ($pNode instanceof Node)) {
			$this->mNextNodes[] = $pNode;
		}
	}
	
	public function popNeighbor() {
		return array_pop($this->mNextNodes);
	}
	
	public function unshiftNeighbor($pNode) {
		if (is_object($pNode) && ($pNode instanceof Node)) {
			array_unshift($this->mNextNodes, $pNode);
		}
	}
	
	public function shiftNeighbor() {
		return array_shift($this->mNextNodes);
	}
	
	/**
	 * replace neighbor and return old neighbor if success
	 * @param unknown $pNode
	 * @param unknown $pIndex
	 * @return boolean|Node
	 */
	public function replaceNeighborAt($pNode, $pIndex) {
		$lReturn = false;
		if (array_key_exists($pIndex, $this->mNextNodes) && is_object($pNode) && ($pNode instanceof Node)) {
			$lReturn = $this->mNextNodes[$pIndex];
			$this->mNextNodes[$pIndex] = $pNode;
		}
		return $lReturn;
	}
	
	/**
	 * replace neighbor node with the same instance as $pToReplaceNode and return it if success
	 * @param unknown $pIndex
	 * @return boolean|Node
	 */
	public function replaceNeighbor($pNewNode, $pToReplaceNode) {
		$lReturn = false;
		for ($i = 0; $i < count($this->mNextNodes); $i++) {
			if ($this->mNextNodes[$i] === $pToReplaceNode) {
				$lReturn = $this->replaceNeighborAt($pNewNode, $i);
				break;
			}
		}
		return $lReturn;
	}
	
	/**
	 * delete neighbor and return old neighbor if success
	 * @param unknown $pIndex
	 * @return boolean|Node
	 */
	public function deleteNeighborAt($pIndex) {
		$lReturn = false;
		if (array_key_exists($pIndex, $this->mNextNodes)) {
			$lReturn = $this->mNextNodes[$pIndex];
			unset($this->mNextNodes[$pIndex]);
			$this->mNextNodes = array_values($this->mNextNodes);
		}
		return $lReturn;
	}
	
	/**
	 * delete neighbor node with the same instance as $pNode and return it if success
	 * @param unknown $pIndex
	 * @return boolean|Node
	 */
	public function deleteNeighbor($pNode) {
		$lReturn = false;
		for ($i = 0; $i < count($this->mNextNodes); $i++) {
			if ($this->mNextNodes[$i] === $pNode) {
				$lReturn = $this->deleteNeighborAt($i);
				break;
			}
		}
		return $lReturn;
	}
	
	/**
	 * delete neighbors all and return them
	 * @param unknown $pIndex
	 * @return array
	 */
	public function deleteNeighbors() {
		$lReturn = $this->mNextNodes;
		$this->mNextNodes = [];
		return $lReturn;
	}
	
	public function hasNeighborAt($pIndex) {
		return array_key_exists($pIndex, $this->mNextNodes);
	}
	
	public function hasNeighbors() {
		return !empty($this->mNextNodes);
	}
	
	public function getNeighborAt($pIndex) {
		return array_key_exists($pIndex, $this->mNextNodes) ? $this->mNextNodes[$pIndex] : null;
	}
	
	public function getChildren() {
		return $this->mNextNodes;
	}
	
	public function pushChild($pNode) {
		$this->pushNeighbor($pNode);
	}
	
	public function popChild() {
		return $this->popNeighbor();
	}
	
	public function unshiftChild($pNode) {
		$this->unshiftNeighbor($pNode);
	}
	
	public function shiftChild() {
		return $this->shiftNeighbor();
	}
	
	public function replaceChildAt($pNode, $pIndex) {
		return $this->replaceNeighborAt($pNode, $pIndex);
	}
	
	public function deleteChildAt($pIndex) {
		return $this->deleteNeighborAt($pIndex);
	}
	
	public function hasChildAt($pIndex) {
		return $this->hasNeighborAt($pIndex);
	}
	
	public function hasChildren() {
		return $this->hasNeighbors();
	}
	
	public function getChildAt($pIndex) {
		return $this->getNeighborAt($pIndex);
	}
}