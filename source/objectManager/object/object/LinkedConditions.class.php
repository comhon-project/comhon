<?php

class LinkedConditions {

	private $mLink;
	private $mConditions = array();
	private $mLinkedConditions = array();
	
	private static $sAcceptedLinks = array(
		"and" => null,
		"or" => null
	);
	
	/**
	 * 
	 * @param string $pLink can be "and" or "or"
	 */
	public function __construct($pLink) {
		if (array_key_exists($pLink, self::$sAcceptedLinks)) {
			$this->mLink = $pLink;
		}
	}
	
	public function getLink() {
		return $this->mLink;
	}
	
	/**
	 * @param Condition $pCondition
	 */
	public function addCondition($pCondition) {
		$this->mConditions[] = $pCondition;
	}
	
	/**
	 * @param LinkedConditions $pLinkedConditions
	 */
	public function addLinkedConditions($pLinkedConditions) {
		$this->mLinkedConditions[] = $pLinkedConditions;
	}
	
	/**
	 * @param array $pConditions
	 */
	public function setConditions($pConditions) {
		$this->mConditions = $pConditions;
	}
	
	/**
	 * @param array $pLinkedConditions
	 */
	public function setLinkedConditions($pLinkedConditions) {
		$this->mLinkedConditions = $pLinkedConditions;
	}
	
	public function getConditions() {
		return $this->mConditions;
	}
	
	public function getLinkedConditions() {
		return $this->mLinkedConditions;
	}
	
	/**
	 * 
	 * @param string $pKeyType can be "index" or "md5"
	 * @return multitype:
	 */
	public function getFlattenedConditions($pKeyType = "index") {
		$lConditions = array();
		$this->getFlattenedConditionsWithRefParam($lConditions, $pKeyType);
		return $lConditions;
	}
	
	/**
	 * don't call this function, call getFlattenedConditions
	 * @param array $pConditions
	 * @param array $pKeyType
	 */
	public function getFlattenedConditionsWithRefParam(&$pConditions, $pKeyType) {
		foreach ($this->mConditions as $lCondition) {
			switch ($pKeyType) {
				case "md5":
					$pConditions[md5($lCondition->export())] = $lCondition;
					break;
				default:
					$pConditions[] = $lCondition;
					break;
			}
		}
		foreach ($this->mLinkedConditions as $lLinkedConditions) {
			$lLinkedConditions->getFlattenedConditionsWithRefParam($pConditions, $pKeyType);
		}
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		$lArray = array();
		foreach ($this->mConditions as $lCondition) {
			$lArray[] = $lCondition->export($pValues);
		}
		foreach ($this->mLinkedConditions as $lLinkedConditions) {
			$lResult = $lLinkedConditions->export($pValues);
			if ($lResult != "") {
				$lArray[] = $lResult;
			}
		}
		return (count($lArray) > 0) ? "(".implode(" ".$this->mLink." ", $lArray).")" : "";
	}
	
	public function hasOnlyOneCondition() {
		$lhasOnlyOneCondition = false;
		if (count($this->mConditions) > 1) {
			return false;
		}elseif (count($this->mConditions) == 1) {
			$lhasOnlyOneCondition = true;
		}
		foreach ($this->mLinkedConditions as $lLinkedConditions) {
			if ($lLinkedConditions->hasConditions()) {
				if ($lhasOnlyOneCondition) {
					return false;
				}elseif ($lLinkedConditions->hasOnlyOneCondition()) {
					$lhasOnlyOneCondition = true;
				}else {
					return false;
				}
			}
		}
		return $lhasOnlyOneCondition;
	}
	
	public function hasConditions() {
		if (count($this->mConditions) > 0) {
			return true;
		}foreach ($this->mLinkedConditions as $lLinkedConditions) {
			if ($lLinkedConditions->hasConditions()) {
				return true;
			}
		}
		return false;
	}
	
	public function isSatisfied($pPredicates) {
		$lReturn = false;
		if ($this->mLink == "and") {
			$lReturn = $this->_isSatisfiedAnd($pPredicates);
		}elseif ($this->mLink == "or") {
			$lReturn = $this->_isSatisfiedOr($pPredicates);
		}
		return $lReturn;
	}
	
	private function _isSatisfiedAnd($pPredicates) {
		foreach ($this->mConditions as $lKey => $lCondition) {
			if (!$pPredicates[$lKey]) {
				return false;
			}
		}
		foreach ($this->mLinkedConditions as $lLinkedConditions) {
			if (!$lLinkedConditions->isSatisfied($pPredicates)) {
				return false;
			}
		}
		return true;
	}
	
	private function _isSatisfiedOr($pPredicates) {
		$lSatisfied = false;
		foreach ($this->mConditions as $lKey => $lCondition) {
			$lSatisfied = $lSatisfied || $pPredicates[$lKey];
		}
		foreach ($this->mLinkedConditions as $lLinkedConditions) {
			$lSatisfied = $lSatisfied || $lLinkedConditions->isSatisfied($pPredicates);
		}
		return $lSatisfied;
	}
	
}