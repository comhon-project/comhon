<?php
namespace comhon\utils\structure;

class UndirectedGraph extends TreeStructure {
	
	/*********************************************************              insert functions              *********************************************************/
	
	public function pushNeighborNode(Node $pNode) {
		return $this->_pushNeighborNode($pNode);
	}
	
	/**
	 * insert value between current node and neighbor at index $pNeighborIndex
	 * @param unknown $pNode
	 * @param unknown $pNeighborIndex
	 */
	public function insertNeighborNode(Node $pNode, $pNeighborIndex) {
		return $this->_insertNeighborNode($pNode, $pNeighborIndex);
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete all references in the graph to the neighbor node
	 * @param integer $pNeighborIndex
	 * @return boolean|Node
	 */
	public function deleteNeighborAt($pNeighborIndex) {
		return $this->_deleteNeighborAt($pNeighborIndex);
	}
	
}