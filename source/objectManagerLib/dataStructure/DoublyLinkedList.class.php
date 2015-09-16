<?php
namespace objectManagerLib\dataStructure;

/**
 * this class offer an alternative to the native class "SplDoublyLinkedList" which has a very very very very very strange behaviour
 */
class DoublyLinkedList {
	
	private $mFirstNode;
	private $mLastNode;
	private $mCurrentNode;
	
	public function __construct() {
		
	}
	
	public function reset() {
		$this->mFirstNode   = null;
		$this->mLastNode    = null;
		$this->mCurrentNode = null;
	}
	
	/*********************************************************              access functions              *********************************************************/
	
	/**
	 * return the value of current node or null if there is no current node
	 * Warning!!! function can return null because there is no current node or because the value of current node is null
	 */
	public function current() {
		$lReturn = null;
		if (!is_null($this->mCurrentNode)) {
			$lReturn = $this->mCurrentNode->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return the value of previous node or null if there is no previous node
	 * Warning!!! function can return null because there is no previous node or because the value of previous node is null
	 */
	public function getPrevious() {
		$lReturn = null;
		if (!is_null($this->mCurrentNode) && !is_null($this->mCurrentNode->getPrevious())) {
			$lReturn = $this->mCurrentNode->getPrevious()->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return the value of next node or null if there is no next node
	 * Warning!!! function can return null because there is no next node or because the value of next node is null
	 */
	public function getNext() {
		$lReturn = null;
		if (!is_null($this->mCurrentNode) && !is_null($this->mCurrentNode->getNext())) {
			$lReturn = $this->mCurrentNode->getNext()->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return the value of first node or null if there is no current node
	 * Warning!!! function can return null because there is no first node or because the value of first node is null
	 */
	public function first() {
		$lReturn = null;
		if (!is_null($this->mFirstNode)) {
			$lReturn = $this->mFirstNode->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return the value of last node or null if there is no current node
	 * Warning!!! function can return null because there is no last node or because the value of last node is null
	 */
	public function last() {
		$lReturn = null;
		if (!is_null($this->mLastNode)) {
			$lReturn = $this->mLastNode->getValue();
		}
		return $lReturn;
	}
	
	/**
	 * return true if there is no node
	 */
	public function isEmpty() {
		return is_null($this->mCurrentNode);
	}
	
	/**
	 * return true if the current node is the last node of the linked list
	 */
	public function isLast() {
		return is_null($this->mCurrentNode->getNext());
	}
	
	/**
	 * return true if the current node is the first node of the linked list
	 */
	public function isFirst() {
		return is_null($this->mCurrentNode->getPrevious());
	}
	
	/*********************************************************              navigate functions              *********************************************************/

	/**
	 * if it is possible, move the current node to the previous node
	 * return the new current node or false if there is no previous node
	 */
	public function previous() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			if (!is_null($lPreviousNode = $this->mCurrentNode->getPrevious())) {
				$this->mCurrentNode =  $lPreviousNode;
				$lReturn = true;
			}
		}
		return $lReturn;
	}
	
	
	/**
	 * if it is possible, move the current node to the next node
	 * return the new current node or false if there is no next node
	 */
	public function next() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			if (!is_null($lNextNode = $this->mCurrentNode->getNext())) {
				$this->mCurrentNode =  $lNextNode;
				$lReturn = true;
			}
		}
		return $lReturn;
	}
	
	/**
	 * if it is possible, move the current node to the next node
	 * return the new current node or false if there is no next node
	 */
	public function rewind() {
		$this->mCurrentNode =  $this->mFirstNode;
	}
	
	/*********************************************************              insert functions              *********************************************************/
	
	/**
	 * create and insert a node before or after the current node
	 * @param value $pValue the value that will be in the new node
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	public function insert($pValue, $pPosition = 1) {
		$lNode = new Node($pValue, null, array(null));
		$this->_insert($lNode, $pPosition);
	}
	
	/**
	 * insert a node before or after the current node
	 * @param Node $pNode the node to insert
	 * @param integer $pPosition the position where to insert the node (-1 => before the current node, 1 => after the current node). by default set to 1
	 */
	private function _insert($pNode, $pPosition) {
		if (is_null($this->mCurrentNode)) {
			$this->mFirstNode = $pNode;
			$this->mCurrentNode = $pNode;
			$this->mLastNode = $pNode;
		}
		else {
			if ($pPosition === -1) {
				$lOldPrevious = $this->mCurrentNode->getPrevious();
				if (!is_null($lOldPrevious)) {
					$lOldPrevious->setNext($pNode);
					$pNode->setPrevious($lOldPrevious);
				}else {
					$this->mFirstNode = $pNode;
				}
				$this->mCurrentNode->setPrevious($pNode);
				$pNode->setNext($this->mCurrentNode);
			}
			else if ($pPosition === 1) {
				$lOldNext = $this->mCurrentNode->getNext();
				if (!is_null($lOldNext)) {
					$lOldNext->setPrevious($pNode);
					$pNode->setNext($lOldNext);
				}else {
					$this->mLastNode = $pNode;
				}
				$this->mCurrentNode->setNext($pNode);
				$pNode->setPrevious($this->mCurrentNode);
			}
		}
	}
	
	/**
	 * create and insert a node at the beginning of the the doubly linked list (in O(1))
	 * @param value $pValue the value that will be in the new node
	 */
	public function unshift($pValue) {
		$lNode = new Node($pValue, null, array(null));
		if (!is_null($this->mCurrentNode)) {
			$lCurrentNode = $this->mCurrentNode;
			$this->mCurrentNode = $this->mFirstNode;
			$this->_insert($lNode, -1);
			$this->mCurrentNode = $lCurrentNode;
		}else {
			$this->_insert($lNode, 1);
		}
	}
	
	/**
	 * create and insert a node at the end of the the doubly linked list (in O(1))
	 * @param value $pValue the value that will be in the new node
	 */
	public function push($pValue) {
		$lNode = new Node($pValue, null, array(null));
		if (!is_null($this->mCurrentNode)) {
			$lCurrentNode = $this->mCurrentNode;
			$this->mCurrentNode = $this->mLastNode;
			$this->_insert($lNode, 1);
			$this->mCurrentNode = $lCurrentNode;
		}else {
			$this->_insert($lNode, 1);
		}
	}
	
	/*********************************************************              delete functions              *********************************************************/
	
	/**
	 * delete the current node and move to the next or the previous node
	 * @param integer $pPosition the position where to move after deleting (-1 => before the deleted node, 1 => after the deleted node). by default set to 1
	 */
	public function deleteCurrent($pPosition = 1) {
		$lReturn = true;
		if (!is_null($this->mCurrentNode)) {
			// if there is only one node just set all values to null
			if (is_null($this->mCurrentNode->getPrevious()) && is_null($this->mCurrentNode->getNext())) {
				$this->mFirstNode = null;
				$this->mCurrentNode = null;
				$this->mLastNode = null;
			}else {
				if ($pPosition === -1) {
					$lFutureCurrent = $this->mCurrentNode->getPrevious();
					if (!is_null($lFutureCurrent)) {
						if (!is_null($this->mCurrentNode->getNext())) {
							$this->mCurrentNode->getNext()->setPrevious($lFutureCurrent);
						}else {
							$this->mLastNode = $lFutureCurrent;
						}
						$lFutureCurrent->setNext($this->mCurrentNode->getNext());
						$this->mCurrentNode = $lFutureCurrent;
					}else {
						$lFutureCurrent =  $this->mCurrentNode->getNext();
						$lFutureCurrent->setPrevious(null);
						$this->mFirstNode = $lFutureCurrent;
						$this->mCurrentNode = $lFutureCurrent;
					}
				}
				else if ($pPosition === 1) {
					$lFutureCurrent = $this->mCurrentNode->getNext();
					if (!is_null($lFutureCurrent)) {
						if (!is_null($this->mCurrentNode->getPrevious())) {
							$this->mCurrentNode->getPrevious()->setNext($lFutureCurrent);
						}else {
							$this->mFirstNode = $lFutureCurrent;
						}
						$lFutureCurrent->setPrevious($this->mCurrentNode->getPrevious());
						$this->mCurrentNode = $lFutureCurrent;
					}else {
						$lFutureCurrent =  $this->mCurrentNode->getPrevious();
						$lFutureCurrent->setNext(null);
						$this->mLastNode = $lFutureCurrent;
						$this->mCurrentNode = $lFutureCurrent;
					}
				}else {
					$lReturn = false;
				}
			}
		}else {
			$lReturn = false;
		}
		return $lReturn;
	}
	
	/**
	 * if possible, delete the previous node
	 */
	public function deletePrevious() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode) && !is_null($lOldPreviousNode = $this->mCurrentNode->getPrevious())) {
			if (!is_null($lOldPreviousNode->getPrevious())) {
				$lOldPreviousNode->getPrevious()->setNext($this->mCurrentNode);
			}else {
				$this->mFirstNode = $this->mCurrentNode;
			}
			$this->mCurrentNode->setPrevious($lOldPreviousNode->getPrevious());
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * if possible, delete the next node
	 */
	public function deleteNext() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode) && !is_null($lOldNextNode = $this->mCurrentNode->getNext())) {
			if (!is_null($lOldNextNode->getNext())) {
				$lOldNextNode->getNext()->setPrevious($this->mCurrentNode);
			}else {
				$this->mLastNode = $this->mCurrentNode;
			}
			$this->mCurrentNode->setNext($lOldNextNode->getNext());
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * if possible, delete the first node (in O(1))
	 */
	public function shift() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			if (is_null($this->mCurrentNode->getPrevious()) && is_null($this->mCurrentNode->getNext())) {
				$this->mFirstNode = null;
				$this->mCurrentNode = null;
				$this->mLastNode = null;
			}else {
				if (is_null($this->mCurrentNode->getPrevious())) {
					$this->mCurrentNode = $this->mCurrentNode->getNext();
					$this->mCurrentNode->setPrevious(null);
					$this->mFirstNode = $this->mCurrentNode;
				}else {
					$this->mFirstNode = $this->mFirstNode->getNext();
					$this->mFirstNode->setPrevious(null);
				}
			}
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * if possible, delete the last node (in O(1))
	 */
	public function pop() {
		$lReturn = false;
		if (!is_null($this->mCurrentNode)) {
			if (is_null($this->mCurrentNode->getPrevious()) && is_null($this->mCurrentNode->getNext())) {
				$this->mFirstNode = null;
				$this->mCurrentNode = null;
				$this->mLastNode = null;
			}else {
				if (is_null($this->mCurrentNode->getNext())) {
					$this->mCurrentNode = $this->mCurrentNode->getPrevious();
					$this->mCurrentNode->setNext(null);
					$this->mLastNode = $this->mCurrentNode;
				}else {
					$this->mLastNode = $this->mLastNode->getPrevious();
					$this->mLastNode->setNext(null);
				}
			}
			$lReturn = true;
		}
		return $lReturn;
	}
	
	/**
	 * cut list
	 * @param integer $pPosition if -1 => cut before current node, else if 1 => cut after current node
	 */
	public function cut($pPosition = 1) {
		if ($pPosition == 1) {
			$this->mCurrentNode->setNext(null);
			$this->mLastNode = $this->mCurrentNode;
		}
		else if ($pPosition == -1) {
			$this->mCurrentNode->setPrevious(null);
			$this->mFirstNode = $this->mCurrentNode;
		}
	}
	
		/*********************************************************              print functions              *********************************************************/
	
	/*
	 * $pFunction is a fonction which will be apply on values in each node (to simplify the output)
	 */
	public function to_pretty_print($pFunction = null) {
		trigger_error($this->to_pretty_string($this->mCurrentNode, $pFunction));
	}
	
	public function to_pretty_string($lNode, $pFunction = null) {
		if (is_null($lNode)) {
			return "[]";
		}
		$lCurrentNode = $lNode;
		$lArray = array();
		
		/*------------ add previous nodes -------------*/
		while (!is_null($lPrevious = $lCurrentNode->getPrevious())) {
			if (is_null($pFunction)) {
				$lValue = $lPrevious->getValue();
			}else {
				$lValue = $lPrevious->getValue()->$pFunction();
			}
			array_unshift($lArray, var_export($lValue, true));
			$lCurrentNode = $lPrevious;
		}
		
		/*------------ add current node -------------*/
		$lCurrentNode = $lNode;
		if (is_null($pFunction)) {
			$lValue = $lCurrentNode->getValue();
		}else {
			$lValue = $lCurrentNode->getValue()->$pFunction();
		}
		$lArray[] = "<<".var_export($lValue, true).">>";
		
		/*------------ add next nodes -------------*/
		while (!is_null($lNext = $lCurrentNode->getNext())) {
			if (is_null($pFunction)) {
				$lValue = $lNext->getValue();
			}else {
				$lValue = $lNext->getValue()->$pFunction();
			}
			$lArray[] = var_export($lValue, true);
			$lCurrentNode = $lNext;
		}
		
		return "[ ".implode(" <=> ", $lArray)." ]";
	}
}