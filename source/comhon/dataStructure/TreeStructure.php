<?php
namespace comhon\dataStructure;

class TreeStructure extends AbstractUndirectedGraph {
	
	
	/*********************************************************              insert functions              *********************************************************/
	
	/**
	 * create and insert a node before or after the current node
	 * @param value $pValue the value that will be in the new node
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	public function pushNeighbor($pValue) {
		$lNeighbor = $this->_pushNeighbor($pValue);
		return $lNeighbor !== false;
	}
	
	/**
	 * insert value between current node and neighbor at index $pNeighborIndex
	 * @param unknown $pNode
	 * @param integer $pNeighborIndex
	 */
	public function insertNeighbor($pValue, $pNeighborIndex) {
		$lOldNeighbor = $this->_insertNeighbor($pValue, $pNeighborIndex);
		return $lOldNeighbor !== false;
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete link between current node and neighbor at index $pNeighborIndex
	 * @param integer $pNeighborIndex
	 * @return boolean|Node
	 */
	public function deleteNeighborLinkAt($pNeighborIndex) {
		$lOldNeighbor = $this->_deleteNeighborLinkAt($pNeighborIndex);
		return $lOldNeighbor !== false;
	}
	
}