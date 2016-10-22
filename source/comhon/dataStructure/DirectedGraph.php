<?php
namespace comhon\dataStructure;

class directedGraph extends Graph {
	
	/*********************************************************              navigate functions              *********************************************************/
	
	public function goToNeighborAt($pNeighborIndex) {
		return $this->_goToNextNodeAt($pNeighborIndex);
	}
	
	/*********************************************************              insert functions              *********************************************************/
	
	/**
	 * create and insert a node before or after the current node
	 * @param value $pValue the value that will be in the new node
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	public function pushNeighbor($pValue) {
		return $this->_pushNeighbor($pValue);
	}
	
	public function pushNeighborNode(Node $pNode) {
		return $this->_pushNeighborNode($pNode);
	}
	
	/**
	 * insert value between current node and child at index $pChildIndex
	 * @param unknown $pNode
	 * @param integer $pChildIndex
	 */
	public function insertNeighbor($pValue, $pChildIndex) {
		return $this->_insertNeighbor($pValue, $pChildIndex);
	}
	
	/**
	 * insert value between current node and child at index $pChildIndex
	 * @param unknown $pNode
	 * @param unknown $pChildIndex
	 */
	public function insertNeighborNode(Node $pNode, $pChildIndex) {
		return $this->_insertNeighborNode($pNode, $pChildIndex);
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete link between current node and child at index $pChildIndex
	 * @param integer $pChildIndex
	 * @return boolean|Node
	 */
	public function deleteNeighborLinkAt($pChildIndex) {
		return $this->_deleteNeighborLinkAt($pChildIndex);
	}
	
}