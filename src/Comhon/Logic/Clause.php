<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Logic;

use Comhon\Database\DbLiteral;
use Comhon\Exception\ArgumentException;
use Comhon\Object\UniqueObject;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ComhonException;

/**
 * logical junction is actually a disjunction or a conjunction
 * - a disjunction is true if at least one of elements of this disjonction is true
 * - a conjunction is true if all elements of this conjunction are true
 */
class Clause extends Formula {

	/** @var string */
	const DISJUNCTION = 'disjunction';
	
	/** @var string */
	const CONJUNCTION = 'conjunction';
	
	/** @var string */
	protected $type;
	
	/** @var Formula[] */
	protected $formulas= [];
	
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
			throw new ArgumentException($type, self::$allowedTypes, 1);
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
	 * add formula element
	 *
	 * @param Formula $formula
	 */
	public function addElement(Formula $formula) {
		$this->formulas[] = $formula;
	}
	
	/**
	 * add literal
	 * 
	 * @param Literal $literal
	 */
	public function addLiteral(Literal $literal) {
		$this->formulas[] = $literal;
	}
	
	/**
	 * add logical junction
	 * 
	 * @param Clause $clause
	 */
	public function addClause(Clause $clause) {
		$this->formulas[] = $clause;
	}
	
	/**
	 * get formula elements
	 * 
	 * @return Formula[]
	 */
	public function getElements() {
		return $this->formulas;
	}
	
	/**
	 * extract literals from formula direct elements
	 * 
	 * @param boolean $indexByMD5 if true index literals by md5 (calcul all md5 for each call)
	 * @return Literal[]:
	 */
	public function getLiterals($indexByMD5 = false) {
		$literals = [];
		if ($indexByMD5) {
			foreach ($this->formulas as $formula) {
				if ($formula instanceof Literal) {
					$literals[md5($formula->exportDebug())] = $formula;
				}
			}
		}else {
			foreach ($this->formulas as $formula) {
				if ($formula instanceof Literal) {
					$literals[] = $formula;
				}
			}
		}
		return $literals;
	}
	
	/**
	 * extract clauses from formula direct elements
	 * 
	 * @return Clause[]
	 */
	public function getClauses() {
		$clauses = [];
		foreach ($this->formulas as $formula) {
			if ($formula instanceof Clause) {
				$clauses[] = $formula;
			}
		}
		return $clauses;
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
		foreach ($this->getLiterals() as $literal) {
			if ($indexByMD5) {
				$literals[md5($literal->exportDebug())] = $literal;
			} else {
				$literals[] = $literal;
			}
		}
		foreach ($this->getClauses() as $clause) {
			$clause->_getFlattenedLiteralsWithRefParam($literals, $indexByMD5);
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
		foreach ($this->formulas as $formula) {
			$result = $formula->export($values);
			if ($result !== '') {
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
		foreach ($this->formulas as $formula) {
			$result = $formula->exportDebug();
			if ($result != '') {
				$array[] = $result;
			}
		}
		return !empty($array) ? '('.implode(' '.$this->getOperator().' ', $array).')' : '';
	}
	
	/**
	 * verify if logical junction contain one and only one literal
	 * 
	 * search recursively in clauses
	 * 
	 * @return boolean
	 */
	public function hasOnlyOneLiteral() {
		$hasOnlyOneLiteral = false;
		$literals = $this->getLiterals();
		if (count($literals) > 1) {
			return false;
		}elseif (count($literals) == 1) {
			$hasOnlyOneLiteral = true;
		}
		foreach ($this->getClauses() as $clause) {
			if ($clause->hasLiterals()) {
				if ($hasOnlyOneLiteral) {
					return false;
				}elseif ($clause->hasOnlyOneLiteral()) {
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
		foreach ($this->formulas as $formula) {
			if ($formula instanceof Literal) {
				return true;
			} elseif ($formula->hasLiterals()) {
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
		$isSatisfied = false;
		if ($this->type == self::CONJUNCTION) {
			$isSatisfied = $this->_isSatisfiedConjunction($predicates);
		}elseif ($this->type == self::DISJUNCTION) {
			$isSatisfied = $this->_isSatisfiedDisjunction($predicates);
		}
		return $isSatisfied;
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
		foreach ($this->getClauses() as $clause) {
			if (!$clause->isSatisfied($predicates)) {
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
		foreach ($this->getClauses() as $clause) {
			$satisfied = $satisfied || $clause->isSatisfied($predicates);
		}
		return $satisfied;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $clause
	 * @param \Comhon\Model\Model[] $modelByNodeId
	 * @param \Comhon\Database\SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @param \Comhon\Database\DbLiteral[] $dbLiteralsById do not specify this parameter, it is used implicitly
	 * @throws \Exception
	 * @return Clause
	 */
	public static function build(UniqueObject $clause, $modelByNodeId, $selectQuery = null, $allowPrivateProperties = true, &$dbLiteralsById = []) {
		$clauseModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Clause');
		if (!$clause->getModel()->isInheritedFrom($clauseModel)) {
			throw new ArgumentException($clause, $clauseModel->getObjectInstance(false)->getComhonClass(), 1);
		}
		
		$type = $clause->getModel()->getName() == 'Comhon\Logic\Simple\Clause\Conjunction' ? Clause::CONJUNCTION : Clause::DISJUNCTION;
		$newClause = new Clause($type);
		foreach ($clause->getValue('elements') as $element) {
			if ($element->getModel()->isInheritedFrom($clauseModel)) { // clause
				$newClause->addClause(Clause::build($element, $modelByNodeId, $selectQuery, $allowPrivateProperties, $dbLiteralsById));
			} else { // literal
				if (!isset($modelByNodeId[$element->getValue('node')->getId()])) {
					throw new ComhonException("missing key '{$element->getValue('node')->getId()}'");
				}
				$model = $modelByNodeId[$element->getValue('node')->getId()];
				$newClause->addLiteral(DbLiteral::build($element, $model, $selectQuery, $allowPrivateProperties, $dbLiteralsById));
			}
		}
		return $newClause;
	}
}