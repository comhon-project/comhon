<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

/**
 * logical junction is actually a disjunction or a conjunction
 * - a disjunction is true if at least one of elements of this disjonction is true
 * - a conjunction is true if all elements of this conjunction are true
 */
class LogicalJunction {

	/** @var string */
	const DISJUNCTION = 'disjunction';
	
	/** @var string */
	const CONJUNCTION = 'conjunction';
	
	/** @var string */
	protected $type;
	
	/** @var Literal[] */
	protected $literals = [];
	
	/** @var LogicalJunction[] */
	protected $logicalJunctions = [];
	
	/** @var string[] */
	private static $allowedTypes = [
		self::DISJUNCTION => 'or',
		self::CONJUNCTION => 'and'
	];
	
	/**
	 * 
	 * @param string $type can be self::CONJUNCTION or self::DISJUNCTION
	 */
	public function __construct($type) {
		if (!array_key_exists($type, self::$allowedTypes)) {
			throw new \Exception("type '$type' doesn't exists");
		}
		$this->type = $type;
	}
	
	/**
	 * get type
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * get sql operator
	 * 
	 * @return string
	 */
	public function getOperator() {
		return self::$allowedTypes[$this->type];
	}
	
	/**
	 * add literal
	 * 
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->literals[] = $literal;
	}
	
	/**
	 * add logical junction
	 * 
	 * @param LogicalJunction $logicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $logicalJunction) {
		$this->logicalJunctions[] = $logicalJunction;
	}
	
	/**
	 * @param Literal[] $literals
	 */
	public function setLiterals($literals) {
		$this->literals = $literals;
	}
	
	/**
	 * @param LogicalJunction[] $logicalJunctions
	 */
	public function setLogicalJunction($logicalJunctions) {
		$this->logicalJunctions = $logicalJunctions;
	}
	
	/**
	 * @param boolean $indexByMD5 if true index literals by md5 (calcul all md5 for each call)
	 * @return Literal[]:
	 */
	public function getLiterals($indexByMD5 = false) {
		$return = $this->literals;
		if ($indexByMD5) {
			$return = [];
			foreach ($this->literals as $literal) {
				$return[md5($literal->exportWithValue())] = $literal;
			}
		}
		return $return;
	}
	
	/**
	 * 
	 * @return LogicalJunction[]
	 */
	public function getLogicalJunctions() {
		return $this->logicalJunctions;
	}
	
	/**
	 * 
	 * @param boolean $indexByMD5 if true index literals by md5 (calcul all md5 for each call)
	 * @return Literal[]
	 */
	public function getFlattenedLiterals($indexByMD5 = false) {
		$literals= [];
		$this->_getFlattenedLiteralsWithRefParam($literals, $indexByMD5);
		return $literals;
	}
	
	/**
	 * @param  Literal[] $literals
	 * @param boolean $indexByMD5 if true index literals by md5 (calcul all md5 for each call)
	 */
	protected function _getFlattenedLiteralsWithRefParam(&$literals, $indexByMD5) {
		foreach ($this->literals as $literal) {
			if ($indexByMD5) {
				$literals[md5($literal->exportWithValue())] = $literal;
			} else {
				$literals[] = $literal;
			}
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			$logicalJunction->_getFlattenedLiteralsWithRefParam($literals, $indexByMD5);
		}
	}
	
	/**
	 * export stringified logical junction to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		$array = [];
		foreach ($this->literals as $literal) {
			$array[] = $literal->export($values);
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			$result = $logicalJunction->export($values);
			if ($result != '') {
				$array[] = $result;
			}
		}
		return (!empty($array)) ? '('.implode(' '.$this->getOperator().' ', $array).')' : '';
	}
	
	/**
	 * export stringified logical junction to integrate it in sql query
	 * DO NOT USE this function to build a query that will be executed (it doesn't prevent from injection)
	 * USE this function to see what query looks like
	 *
	 * @return string
	 */
	public function exportDebug() {
		$array = [];
		foreach ($this->literals as $literal) {
			$array[] = $literal->exportWithValue();
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			$result = $logicalJunction->exportDebug();
			if ($result != '') {
				$array[] = $result;
			}
		}
		return (!empty($array)) ? '('.implode(' '.$this->getOperator().' ', $array).')' : '';
	}
	
	/**
	 * verify if logical junction contain one and only one literal
	 * 
	 * @return boolean
	 */
	public function hasOnlyOneLiteral() {
		$hasOnlyOneLiteral = false;
		if (count($this->literals) > 1) {
			return false;
		}elseif (count($this->literals) == 1) {
			$hasOnlyOneLiteral = true;
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			if ($logicalJunction->hasLiterals()) {
				if ($hasOnlyOneLiteral) {
					return false;
				}elseif ($logicalJunction->hasOnlyOneLiteral()) {
					$hasOnlyOneLiteral = true;
				}else {
					return false;
				}
			}
		}
		return $hasOnlyOneLiteral;
	}
	
	/**
	 * verify if logical junction contain at least one literal
	 * 
	 * @return boolean
	 */
	public function hasLiterals() {
		if (!empty($this->literals)) {
			return true;
		}foreach ($this->logicalJunctions as $logicalJunction) {
			if ($logicalJunction->hasLiterals()) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * @param boolean[] $predicates
	 * @return boolean
	 */
	public function isSatisfied($predicates) {
		$return = false;
		if ($this->type == self::CONJUNCTION) {
			$return = $this->_isSatisfiedConjunction($predicates);
		}elseif ($this->type == self::DISJUNCTION) {
			$return = $this->_isSatisfiedDisjunction($predicates);
		}
		return $return;
	}
	
	/**
	 *
	 * @param boolean[] $predicates
	 * @return boolean
	 */
	private function _isSatisfiedConjunction($predicates) {
		foreach ($this->getLiterals(true) as $key => $literal) {
			if (!$predicates[$key]) {
				return false;
			}
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			if (!$logicalJunction->isSatisfied($predicates)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 *
	 * @param boolean[] $predicates
	 * @return boolean
	 */
	private function _isSatisfiedDisjunction($predicates) {
		$satisfied = false;
		foreach ($this->getLiterals(true) as $key => $literal) {
			$satisfied = $satisfied || $predicates[$key];
		}
		foreach ($this->logicalJunctions as $logicalJunction) {
			$satisfied = $satisfied || $logicalJunction->isSatisfied($predicates);
		}
		return $satisfied;
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @param \Comhon\Model\Model[] $modelByNodeId
	 * @param Literal[] $literalCollection
	 * @param SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return LogicalJunction
	 */
	public static function stdObjectToLogicalJunction($stdObject, $modelByNodeId, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		if (!isset($stdObject->type) || (isset($stdObject->logicalJunctions) && !is_array($stdObject->logicalJunctions)) || (isset($stdObject->literals) && !is_array($stdObject->literals))) {
			throw new \Exception('malformed stdObject LogicalJunction : '.json_encode($stdObject));
		}
		$logicalJunction = new LogicalJunction($stdObject->type);
		if (isset($stdObject->logicalJunctions)) {
			foreach ($stdObject->logicalJunctions as $stdObjectLogicalJunction) {
				$logicalJunction->addLogicalJunction(LogicalJunction::stdObjectToLogicalJunction($stdObjectLogicalJunction, $modelByNodeId, $literalCollection, $selectQuery, $allowPrivateProperties));
			}
		}
		if (isset($stdObject->literals)) {
			foreach ($stdObject->literals as $stdObjectLiteral) {
				if (isset($stdObjectLiteral->id)) {
					$model = null;
				} else if (isset($stdObjectLiteral->node) && array_key_exists($stdObjectLiteral->node, $modelByNodeId)) {
					$model = $modelByNodeId[$stdObjectLiteral->node];
				} else {
					throw new \Exception('node doesn\' exists or not recognized'.json_encode($stdObjectLiteral));
				}
				$logicalJunction->addLiteral(Literal::stdObjectToLiteral($stdObjectLiteral, $model, $literalCollection, $selectQuery, $allowPrivateProperties));
			}
		}
		return $logicalJunction;
	}
}