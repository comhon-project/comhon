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

	const DISJUNCTION = 'disjunction';
	const CONJUNCTION = 'conjunction';
	
	protected $type;
	protected $literals = [];
	protected $logicalJunction = [];
	
	private static $acceptedTypes = [
		self::DISJUNCTION => 'or',
		self::CONJUNCTION => 'and'
	];
	
	/**
	 * 
	 * @param string $type can be self::CONJUNCTION or self::DISJUNCTION
	 */
	public function __construct($type) {
		if (!array_key_exists($type, self::$acceptedTypes)) {
			throw new \Exception("type '$type' doesn't exists");
		}
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getOperator() {
		return self::$acceptedTypes[$this->type];
	}
	
	/**
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->literals[] = $literal;
	}
	
	/**
	 * @param LogicalJunction $logicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $logicalJunction) {
		$this->logicalJunction[] = $logicalJunction;
	}
	
	/**
	 * @param array $literals
	 */
	public function setLiterals($literals) {
		$this->literals = $literals;
	}
	
	/**
	 * @param array $logicalJunction
	 */
	public function setLogicalJunction($logicalJunction) {
		$this->logicalJunction = $logicalJunction;
	}
	
	/**
	 * @param string $keyType can be "index" or "md5"
	 * @return array:
	 */
	public function getLiterals($keyType = 'index') {
		$return = $this->literals;
		if ($keyType == 'md5') {
			$return = [];
			foreach ($this->literals as $literal) {
				$return[md5($literal->exportWithValue())] = $literal;
			}
		}
		return $return;
	}
	
	public function getLogicalJunction() {
		return $this->logicalJunction;
	}
	
	/**
	 * 
	 * @param string $keyType can be "index" or "md5"
	 * @return array
	 */
	public function getFlattenedLiterals($keyType = 'index') {
		$literals= [];
		$this->getFlattenedLiteralsWithRefParam($literals, $keyType);
		return $literals;
	}
	
	/**
	 * don't call this function, call getFlattenedLiterals
	 * @param array $literals
	 * @param array $keyType
	 */
	public function getFlattenedLiteralsWithRefParam(&$literals, $keyType) {
		foreach ($this->literals as $literal) {
			switch ($keyType) {
				case 'md5':
					$literals[md5($literal->exportWithValue())] = $literal;
					break;
				default:
					$literals[] = $literal;
					break;
			}
		}
		foreach ($this->logicalJunction as $logicalJunction) {
			$logicalJunction->getFlattenedLiteralsWithRefParam($literals, $keyType);
		}
	}
	
	/**
	 * @param array $values
	 * @return string
	 */
	public function export(&$values) {
		$array = [];
		foreach ($this->literals as $literal) {
			$array[] = $literal->export($values);
		}
		foreach ($this->logicalJunction as $logicalJunction) {
			$result = $logicalJunction->export($values);
			if ($result != '') {
				$array[] = $result;
			}
		}
		return (!empty($array)) ? '('.implode(' '.$this->getOperator().' ', $array).')' : '';
	}
	
	/**
	 * @return string
	 */
	public function exportDebug() {
		$array = [];
		foreach ($this->literals as $literal) {
			$array[] = $literal->exportWithValue();
		}
		foreach ($this->logicalJunction as $logicalJunction) {
			$result = $logicalJunction->exportDebug();
			if ($result != '') {
				$array[] = $result;
			}
		}
		return (!empty($array)) ? '('.implode(' '.$this->getOperator().' ', $array).')' : '';
	}
	
	public function hasOnlyOneLiteral() {
		$hasOnlyOneLiteral = false;
		if (count($this->literals) > 1) {
			return false;
		}elseif (count($this->literals) == 1) {
			$hasOnlyOneLiteral = true;
		}
		foreach ($this->logicalJunction as $logicalJunction) {
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
	
	public function hasLiterals() {
		if (!empty($this->literals)) {
			return true;
		}foreach ($this->logicalJunction as $logicalJunction) {
			if ($logicalJunction->hasLiterals()) {
				return true;
			}
		}
		return false;
	}
	
	public function isSatisfied($predicates) {
		$return = false;
		if ($this->type == self::CONJUNCTION) {
			$return = $this->_isSatisfiedConjunction($predicates);
		}elseif ($this->type == self::DISJUNCTION) {
			$return = $this->_isSatisfiedDisjunction($predicates);
		}
		return $return;
	}
	
	private function _isSatisfiedConjunction($predicates) {
		foreach ($this->getLiterals('md5') as $key => $literal) {
			if (!$predicates[$key]) {
				return false;
			}
		}
		foreach ($this->logicalJunction as $logicalJunction) {
			if (!$logicalJunction->isSatisfied($predicates)) {
				return false;
			}
		}
		return true;
	}
	
	private function _isSatisfiedDisjunction($predicates) {
		$satisfied = false;
		foreach ($this->getLiterals('md5') as $key => $literal) {
			$satisfied = $satisfied || $predicates[$key];
		}
		foreach ($this->logicalJunction as $logicalJunction) {
			$satisfied = $satisfied || $logicalJunction->isSatisfied($predicates);
		}
		return $satisfied;
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @param Model[] $modelByNodeId
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