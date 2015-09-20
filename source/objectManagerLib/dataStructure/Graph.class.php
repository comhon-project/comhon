<?php
namespace objectManagerLib\dataStructure;

abstract class Graph {
	
	protected $mVisitedNodes;
	protected $mSavedNodes;
	protected $mCurrentNode;
	protected $mCallBackVisit;
	protected $InstanceMap;
	
	public function __construct($pValue) {
		$this->mVisitedNodes = new DoublyLinkedList();
		$this->mSavedNodes = array();
		$this->InstanceMap = array();
		$this->mCurrentNode = new Node($pValue);
	}
	
	/*********************************************************              access functions              *********************************************************/
	
	/**
	 * return the value of current node or null if there is no current node
	 * Warning!!! function can return null because there is no current node or because the value of current node is null
	 */
	public final function current() {
		$lReturn = null;
		if (!is_null($this->mCurrentNode)) {
			$lReturn = $this->mCurrentNode->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return first visited node or null if there is no visited node
	 */
	public final function firstVisitedNode() {
		return $this->mVisitedNodes->first();
	}
	
	/**
	 * return last visited node or null if there is no visited node
	 */
	public final function lastVisitedNode() {
		return $this->mVisitedNodes->last();
	}
	
	/**
	 * return true if there is no node
	 */
	public final function isEmpty() {
		return is_null($this->mCurrentNode);
	}
	
	/**
	 * return true if the current node is the last visited node before going back
	 */
	public final function isLastVisitedNode() {
		return $this->mVisitedNodes->isLast();
	}
	
	/**
	 * return true if the current node is the first node of the linked list
	 */
	public final function isFirstVisitedNode() {
		return $this->mVisitedNodes->isFirst();
	}
	
	/**
	 * add node to saved node list
	 */
	public final function saveNode(Node $pNode) {
		$this->mSavedNodes[] = $pNode;
	}
	
	/**
	 * add current node to saved node list
	 */
	public final function saveCurrentNode() {
		if (!is_null($this->mCurrentNode)) {
			$this->mSavedNodes[] = $this->mCurrentNode;
		}
	}
	
	/**
	 * get saved node value
	 */
	public final function getSavedNodeValue($pIndex) {
		return array_key_exists($pIndex, $this->mSavedNodes) ? $this->mSavedNodes[$pIndex]->getValue() : null;
	}
	
	public final function savedNodesCount() {
		return count($this->mSavedNodes);
	}
	
	/*********************************************************              navigate functions              *********************************************************/
	
	protected function _goToNextNodeAt($pNeighborIndex) {
		$lReturn = false;
		if (!is_null($this->mCurrentNode) && !is_null($lNeighbor = $this->mCurrentNode->getNeighborAt($pNeighborIndex))) {
			if (!$this->mVisitedNodes->isEmpty()) {
				if ($lNeighbor !== $this->mVisitedNodes->getNext()) {
					$this->mVisitedNodes->cut();
					$this->mVisitedNodes->push($lNeighbor);
				}
				$this->mVisitedNodes->next();
			}
			$this->mCurrentNode = $lNeighbor;
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * go to saved node (reset visit nodes)
	 */
	public function goToSavedNodeAt($pIndex) {
		$lReturn = false;
		if (array_key_exists($pIndex, $this->mSavedNodes)) {
			$this->resetVisit();
			$this->mCurrentNode = $this->mSavedNodes[$pIndex];
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * initialize visit (initialize array of visited nodes)
	 * if there is a current node it will be the first visited node
	 * return boolean true if there is a current node, false otherwise
	 */
	public final function initVisit() {
		$lReturn = false;
		$this->mVisitedNodes->reset();
		if (!is_null($this->mCurrentNode)) {
			$this->InstanceMap = null;
			$this->mVisitedNodes->push($this->mCurrentNode);
			$lReturn = true;
		}
		return $lReturn;
	}
	
	public final function resetVisit() {
		$this->mVisitedNodes->reset();
	}
	
	public final function initDepthFirstSearch() {
		if ($this->initVisit()) {
			$this->mCallBackVisit = function () {
				return $this->mVisitedNodes->pop();
			};
			$this->InstanceMap = array();
		}
	}
	
	public final function initBreadthFirstSearch() {
		if ($this->initVisit()) {
			$this->mCallBackVisit = function () {
				return $this->mVisitedNodes->shift();
			};
			$this->InstanceMap = array();
		}
	}
	
	/**
	 * move to next node
	 * @return boolean|node
	 */
	public function next() {
		if($this->mVisitedNodes->isEmpty()) {
			return false;
		} else {
			$lFunction = $this->mCallBackVisit;
			$lLastNode = $lFunction();
			if (is_array($this->InstanceMap) && !array_key_exists(spl_object_hash($lLastNode), $this->InstanceMap)) {
				foreach ($lLastNode->getNeighbors() as $lNode) {
					$this->mVisitedNodes->push($lNode);
				}
			}
			$this->InstanceMap[spl_object_hash($lLastNode)] = null;
			return $lLastNode->getValue();
		}
	}
	
	/**
	 * if it is possible, move the current node to the previous visited node
	 * return the new current node or false if there is no previous node
	 */
	public final function goToPreviousVisitedNode() {
		$lReturn = false;
		if (!$this->mVisitedNodes->isEmpty()) {
			if ($lReturn = $this->mVisitedNodes->previous()) {
				$this->mCurrentNode = $this->mVisitedNodes->current();
			}
		}
		return $lReturn;
	}
	
	
	/**
	 * if it is possible, move the current node to the next node
	 * return the new current node or false if there is no next node
	 */
	public final function goToNextVisitedNode() {
		$lReturn = false;
		if (!$this->mVisitedNodes->isEmpty()) {
			if ($lReturn = $this->mVisitedNodes->next()) {
				$this->mCurrentNode = $this->mVisitedNodes->current();
			}
		}
		return $lReturn;
	}
	
	/*********************************************************              insert functions              *********************************************************/
	
	/**
	 * create and insert a node before or after the current node
	 * @param value $pValue the value that will be in the new node
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	protected function _pushNeighbor($pValue) {
		$lNode = new Node($pValue);
		return $this->_pushNeighborNode($lNode);
	}
	
	protected function _pushNeighborNode(Node $pNode) {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			$this->mCurrentNode->pushNeighbor($pNode);
			$lReturn = $pNode;
		}
		return $lReturn;
	}
	
	/**
	 * insert value between current node and neighbor at index $pNeighborIndex
	 * @param unknown $pNode
	 * @param integer $pNeighborIndex
	 */
	protected function _insertNeighbor($pValue, $pNeighborIndex) {
		$lNode = new Node($pValue);
		return $this->_insertNeighborNode($lNode, $pNeighborIndex);
	}
	
	/**
	 * insert value between current node and neighbor at index $pNeighborIndex
	 * @param unknown $pNode
	 * @param unknown $pNeighborIndex
	 */
	protected function _insertNeighborNode(Node $pNode, $pNeighborIndex) {
		$lReturn = false;
		if (!is_null($this->mCurrentNode) && $this->mCurrentNode->hasNeighborAt($pNeighborIndex)) {
			$lOldNeighbor = $this->mCurrentNode->replaceNeighborAt($pNode, $pNeighborIndex);
			$pNode->pushNeighbor($lOldNeighbor);
			$lReturn = $lOldNeighbor;
		}
		return $lReturn;
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete link between current node and neighbor at index $pNeighborIndex
	 * @param integer $pNeighborIndex
	 * @return boolean|Node
	 */
	protected function _deleteNeighborLinkAt($pNeighborIndex) {
		$lReturn = false;
		if (!$this->mVisitedNodes->isEmpty()) {
			throw new \Exception("can't delete node while visiting, call resetVisit function before");
		}
		return $this->mCurrentNode->deleteNeighborAt($pNeighborIndex);
	}
	
	/**
	 * delete all references in the graph to the neighbor node
	 * @param integer $pNeighborIndex
	 * @return boolean|Node
	 */
	protected function _deleteNeighborAt($pNeighborIndex) {
		$lReturn = false;
		if (!$this->mVisitedNodes->isEmpty()) {
			throw new \Exception("can't delete node while visiting, call resetVisit function before");
		}
		if (!is_null($this->mCurrentNode) && (($lNeighbor = $this->mCurrentNode->getNeighborAt($pNeighborIndex))!== false)) {
			$lReturn = $lNeighbor;
			foreach ($lNeighbor->deleteNeighbors() as $lIndex => $lNeighborNeighbor) {
				$lNeighborNeighbor->deleteNeighbor($lNeighbor);
			}
		}
		return $lReturn;
	}
	
	/*********************************************************              print functions              *********************************************************/
	
	/**
	 * @param function $pCallBack callback applied on each value to simplify the output (value will be a parameter of you callback)
	 */
	public final function to_pretty_print_visited_model($pCallBack = null) {
		if (is_null($pCallBack)) {
			$this->mVisitedNodes->to_pretty_print(function ($pNode) {
				return $pNode->getValue();
			});
		}else {
			$this->mVisitedNodes->to_pretty_print($pCallBack);
		}
	}
}