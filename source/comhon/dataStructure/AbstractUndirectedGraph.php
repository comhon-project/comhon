<?php
namespace comhon\dataStructure;

abstract class AbstractUndirectedGraph extends Graph {
	
	/*********************************************************              navigate functions              *********************************************************/
	
	public function goToNeighborAt($pNeighborIndex) {
		return $this->_goToNextNodeAt($pNeighborIndex);
	}
	
	/*********************************************************              insert functions              *********************************************************/
	
	protected function _pushNeighborNode(Node $pNode) {
		$lNeighbor = parent::_pushNeighborNode($pNode);
		if ($lNeighbor) {
			$pNode->pushNeighbor($this->mCurrentNode);
		}
		return $lNeighbor;
	}
	
	/**
	 * insert value between current node and neighbor at index $pNeighborIndex
	 * @param unknown $pNode
	 * @param unknown $pNeighborIndex
	 */
	protected function _insertNeighborNode(Node $pNode, $pNeighborIndex) {
		$lOldNeighbor = parent::_insertNeighborNode($pNode, $pNeighborIndex);
		if ($lOldNeighbor) {
			$lOldNeighbor->replaceNeighbor($pNode, $this->mCurrentNode);
			$pNode->pushNeighbor($this->mCurrentNode);
		}
		return $lOldNeighbor;
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete link between current node and neighbor at index $pNeighborIndex
	 * @param integer $pNeighborIndex
	 * @return boolean|Node
	 */
	protected function _deleteNeighborLinkAt($pNeighborIndex) {
		$lOldNeighbor = parent::_deleteNeighborLinkAt($pNeighborIndex);
		if ($lOldNeighbor) {
			$lOldNeighbor->deleteNeighbor($this->mCurrentNode);
		}
		return $lOldNeighbor;
	}
	
}