<?php
namespace comhon\dataStructure;

class Tree extends Graph {
	
	private $mRootNode;
	
	public function __construct($pValue) {
		parent::__construct($pValue);
		$this->mRootNode = $this->mCurrentNode;
	}
	
	/**
	 * add last child node to saved node list
	 */
	public final function saveLastChild($pKey = null) {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			$lCount = count($this->mCurrentNode->getChildren());
			if ($lCount > 0) {
				if (is_null($pKey)) {
					$this->mSavedNodes[] = $this->mCurrentNode->getChildAt($lCount - 1);
				} else {
					$this->mSavedNodes[$pKey] = $this->mCurrentNode->getChildAt($lCount - 1);
				}
				$lReturn = true;
			}
		}
		return $lReturn;
	}
	
	/*********************************************************              access functions              *********************************************************/
	
	/**
	 * get child at index $pChildIndex
	 * @param integer $pChildIndex
	 */
	public function getChildAt($pChildIndex) {
		return $this->mCurrentNode->hasChildAt($pChildIndex) ? $this->mCurrentNode->getChildAt($pChildIndex)->getValue() : null;
	}
	
	/**
	 * search a child
	 * @param function $pCallBack
	 * @return integer|false return index of child if exists
	 */
	public function searchChild($pCallBack, $pCallBackParam) {
		$lChildren = $this->mCurrentNode->getChildren();
		for ($i = 0; $i < count($lChildren); $i++) {
			if ($pCallBack($lChildren[$i]->getValue(), $pCallBackParam)) {
				return $i;
			}
		}
		return false;
	}
	
	/**
	 * return true if the current node as neighbors
	 */
	public function hasChildren() {
		return $this->mCurrentNode->hasChildren();
	}
	
	/*********************************************************              navigate functions              *********************************************************/
	
	public function goToChildAt($pChildIndex) {
		return $this->_goToNextNodeAt($pChildIndex);
	}
	
	public function goToParent() {
		$lReturn = false;
		if(!is_null($this->mCurrentNode) && !is_null($this->mCurrentNode->getParent())) {
			if (!$this->mVisitedNodes->isEmpty()) {
				if ($this->mCurrentNode->getParent() !== $this->mVisitedNodes->getNext()) {
					$this->mVisitedNodes->cut();
					$this->mVisitedNodes->push($this->mCurrentNode->getParent());
				}
				$this->mVisitedNodes->next();
			}
			$this->mCurrentNode = $this->mCurrentNode->getParent();
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * go to root node tree (reset visit nodes)
	 */
	public function goToRoot() {
		$this->resetVisit();
		$this->mCurrentNode = $this->mRootNode;
	}
	
	/*********************************************************              insert functions              *********************************************************/
	
	/**
	 * create and insert a node before or after the current node
	 * @param value $pValue the value that will be in the new node
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	public function pushChild($pValue) {
		$lChild = $this->_pushNeighbor($pValue);
		if ($lChild) {
			$lChild->setParent($this->mCurrentNode);
		}
		return $lChild !== false;
	}
	
	/**
	 * insert value between current node and neighbor at index $pChildIndex
	 * @param unknown $pNode
	 * @param integer $pChildIndex
	 */
	public function insertChild($pValue, $pChildIndex) {
		$lOldChild = $this->_insertNeighbor($pValue, $pChildIndex);
		if ($lOldChild) {
			$this->mCurrentNode->getChildAt($pChildIndex)->setParent($this->mCurrentNode);
			$lOldChild->setParent($this->mCurrentNode->getChildAt($pChildIndex));
		}
		return $lOldChild !== false;
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete link between current node and neighbor at index $pChildIndex
	 * @param integer $pChildIndex
	 * @return boolean|Node
	 */
	public function deleteChildAt($pChildIndex) {
		$lChild = $this->_deleteNeighborLinkAt($pChildIndex);
		return ($lChild !== false) ? $lChild->getValue() : false;
	}
	
}