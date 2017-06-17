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

use Comhon\Request\ComplexLoadRequest;
use Comhon\Model\Model;
use Comhon\Model\MainModel;

class Literal {
	
	/** @var string */
	const EQUAL      = '=';
	
	/** @var string */
	const SUPP       = '>';
	
	/** @var string */
	const INF        = '<';
	
	/** @var string */
	const SUPP_EQUAL = '>=';
	
	/** @var string */
	const INF_EQUAL  = '<=';
	
	/** @var string */
	const DIFF       = '<>';
	
	private static $index = 0;

	/** @var string|integer */
	protected $id;
	
	/** @var string */
	
	/** @var TableNode|string */
	protected $table;
	
	/** @var string */
	protected $column;
	
	/** @var string */
	protected $operator;
	
	/** @var string|boolean|number|null|string[]|boolean[]|number[] */
	protected $value;
	
	
	/** @var array */
	protected static $allowedOperators = [
		self::EQUAL      => null,
		self::SUPP       => null,
		self::INF        => null,
		self::SUPP_EQUAL => null,
		self::INF_EQUAL  => null,
		self::DIFF       => null
	];
	
	/** @var array */
	protected static $oppositeOperator = [
		self::EQUAL      => self::DIFF,
		self::INF        => self::SUPP_EQUAL,
		self::INF_EQUAL  => self::SUPP,
		self::SUPP       => self::INF_EQUAL,
		self::SUPP_EQUAL => self::INF,
		self::DIFF       => self::EQUAL
	];
	
	/**
	 * construtor
	 * @param TableNode|string $table
	 * @param string $column 
	 * @param string $operator
	 * @param string|boolean|number|null|string[]|boolean[]|number[] $value 
	 *     if $operator is self::EQUAL or self::DIFF you can specify an array of values 
	 *     operator will be transformed respectively as sql operator IN or NOT IN.
	 *     array can have null value
	 * @throws \Exception
	 */
	public function __construct($table, $column, $operator, $value) {
		$this->table     = $table;
		$this->operator  = $operator;
		$this->value     = $value;
		$this->column    = $column;
		$this->_verifLiteral();
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->operator, self::$allowedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (is_null($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new \Exception('literal with operator \''.$this->operator.'\' can\'t have null value');
		}
		if (is_array($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new \Exception('literal with operator \''.$this->operator.'\' can\'t have array value');
		}
	}

	/**
	 * @return string|number
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @param string|number $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * @return boolean
	 */
	public function hasId() {
		return !is_null($this->id);
	}
	
	/**
	 * 
	 * @return TableNode|string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * @return string
	 */
	public function getPropertyName() {
		return $this->column;
	}
	
	/**
	 * @return string
	 */
	public function getOperator() {
		return $this->operator;
	}
	
	/**
	 * reverse operator
	 * 
	 * exemple :
	 * self::EQUAL become self::DIFF
	 * self::INF become self::SUPP_EQUAL
	 */
	public function reverseOperator() {
		$this->operator = self::$oppositeOperator[$this->operator];
	}
	
	/**
	 * 
	 * @return string|boolean|number|null|string[]|boolean[]|number[]
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * 
	 * @param mixed[] $values values to bind
	 * @return string
	 */
	public function export(&$values) {
		$columnTable = (($this->table instanceof TableNode) ? $this->table->getExportName() : $this->table) . '.' . $this->column;
		if ((($this->operator == '=') || ($this->operator == '<>')) && is_array($this->value)) {
			$i = 0;
			$toReplaceValues = [];
			$hasNullValue = false;
			while ($i < count($this->value)) {
				if (is_null($this->value[$i])) {
					$hasNullValue = true;
				}else {
					$values[] = $this->value[$i];
					$toReplaceValues[] = '?';
				}
				$i++;
			}
			$operator = ($this->operator == '=') ? ' IN ' : ' NOT IN ';
			$toReplaceValues = '('.implode(',', $toReplaceValues).')';
			$stringValue = sprintf('%s %s %s', $columnTable, $operator, $toReplaceValues);
			if ($hasNullValue) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$connector = ($this->operator == '=') ? 'or' : 'and';
				$stringValue = sprintf('(%s %s %s %s)', $stringValue, $connector, $columnTable, $operator);
			}
		}else {
			if (is_null($this->value)) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$stringValue = sprintf('%s %s', $columnTable, $operator);
			}else {
				$values[] = $this->value;
				$stringValue = sprintf('%s %s ?', $columnTable, $this->operator);
			}
		}
		return $stringValue;
	}
	
	/**
	 * export stringified literal to integrate it in sql query
	 * DO NOT USE this function to build a query that will be executed (it doesn't prevent from injection)
	 * USE this function for exemple for debug (it permit to see what query looks like)
	 *
	 * @return string
	 */
	public function exportWithValue() {
		if ((($this->operator == '=') || ($this->operator == '<>')) && is_array($this->value)) {
			$i = 0;
			$toReplaceValues = [];
			$hasNullValue = false;
			while ($i < count($this->value)) {
				if (is_null($this->value[$i])) {
					$hasNullValue = true;
				}else {
					$toReplaceValues[] = $this->value[$i];
				}
				$i++;
			}
			$operator = ($this->operator == '=') ? ' IN ' : ' NOT IN ';
			$toReplaceValues = '('.implode(',', $toReplaceValues).')';
			$stringValue = sprintf('%s.%s %s %s', $this->table, $this->column, $operator, $toReplaceValues);
			if ($hasNullValue) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$connector = ($this->operator == '=') ? 'or' : 'and';
				$stringValue = sprintf('(%s %s %s.%s %s)', $stringValue, $connector, $this->table, $this->column, $operator);
			}
		}else {
			if (is_null($this->value)) {
				$operator = ($this->operator == '=') ? 'is null' : 'is not null';
				$stringValue = sprintf('%s.%s %s', $this->table, $this->column, $operator);
			}else {
				$stringValue = sprintf('%s.%s %s %s', $this->table, $this->column, $this->operator, $this->value);
			}
		}
		return $stringValue;
	}
	
	/**
	 * @param \stdClass $stdObject
	 * @param \Comhon\Model\MainModel $mainModel
	 * @param Literal[] $literalCollection used if $stdObject contain only an id that reference literal in collection
	 * @param SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToLiteral($stdObject, $mainModel, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		if (isset($stdObject->id) && !is_null($literalCollection)) {
			if (!array_key_exists($stdObject->id, $literalCollection)) {
				throw new \Exception("literal id '{$stdObject->id}' is not defined in literal collection");
			}
			return $literalCollection[$stdObject->id];
		}
		self::_verifStdObject($stdObject);
		$table = $stdObject->node;
		
		if (isset($stdObject->queue)) {
			list($joinedTables, $on) = self::_getJoinedTablesFromQueue($mainModel, $stdObject->queue, $allowPrivateProperties);
			$subSelectQuery = self::_setSubSelectQuery($joinedTables, $on, $stdObject, $allowPrivateProperties);
			$rigthTable  = new TableNode($subSelectQuery, 't_'.self::$index++, false);
			
			if (!is_null($selectQuery)) {
				$selectQuery->join(SelectQuery::LEFT_JOIN, $rigthTable, self::_getJoinColumns($table, $rigthTable->getExportName(), $on));
			}
			if (count($on) == 1) {
				$literal = new Literal($rigthTable->getExportName(), $on[0][1], Literal::DIFF, null);
			} else {
				$literal = new NotNullJoinLiteral();
				foreach ($on as $literalArray) {
					$literal->addLiteral($rigthTable->getExportName(), $literalArray[1]);
				}
			}
		}
		else {
			$property =  $mainModel->getProperty($stdObject->property, true);
			if ($property->isAggregation()) {
				throw new \Exception("literal cannot contain aggregation porperty '{$stdObject->property}'");
			}
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new \Exception("literal contain private property '{$property->getName()}'");
			}
			$literal  = new Literal($table, $property->getSerializationName(), $stdObject->operator, $stdObject->value);
		}
		if (isset($stdObject->id)) {
			$literal->setId($stdObject->id);
		}
		return $literal;
	}
	
	/**
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		if (!isset($stdObject->node)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
		}
		if (isset($stdObject->queue)) {
			if (!(isset($stdObject->havingLiteral) xor isset($stdObject->havingLogicalJunction)) || !is_object($stdObject->queue)) {
				throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
			}
		} else if (!isset($stdObject->property) || !isset($stdObject->operator) || !isset($stdObject->value) || !isset($stdObject->node)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
		}
	}
	
	/**
	 * 
	 * @param \Comhon\Model\MainModel $model
	 * @param \stdClass $queue
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return [[], []]
	 * - first element is array of joined tables
	 * - second element is array of columns that will be use for group, select and joins with principale query
	 */
	private static function _getJoinedTablesFromQueue(MainModel $model, $queue, $allowPrivateProperties) {
		$firstTable    = new TableNode($model->getSqlTableUnit()->getSettings()->getValue('name'), null, false);
		$leftTable     = $firstTable;
		$firstModel    = $model;
		$leftModel     = $firstModel;
		$firstNode     = $queue;
		$currentNode   = $firstNode;
		$joinedTables  = [];
		$on            = null;
		
		while (!is_null($currentNode)) {
			if (!is_object($currentNode) || !isset($currentNode->property)) {
				throw new \Exception('malformed stdObject literal : '.json_encode($stdObject));
			}
			$property = $leftModel->getProperty($currentNode->property, true);
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new \Exception("literal contain private property '{$property->getName()}'");
			}
			$leftJoin       = ComplexLoadRequest::prepareJoinedTable($leftTable, $property, self::_getAlias());
			$joinedTables[] = $leftJoin;
			
			
			$leftModel   = $leftJoin['model'];
			$leftTable   = $leftJoin['table'];
			$currentNode = isset($currentNode->child) ? $currentNode->child : null;
		}
		if (!is_null($firstNode)) {
			$on = [];
			if (!($joinedTables[0]['join_on'] instanceof LogicalJunction) || $joinedTables[0]['join_on']->getType() !== LogicalJunction::DISJUNCTION) {
				$firstJoinedTable = $joinedTables[0];
				if ($firstJoinedTable['join_on'] instanceof LogicalJunction) {
					foreach ($firstJoinedTable['join_on']->getLiterals() as $literal) {
						$on[] = [$literal->getPropertyName(), $literal->getColumnRight()];
					}
				} else {
					$on[] = [$firstJoinedTable['join_on']->getPropertyName(), $firstJoinedTable['join_on']->getColumnRight()];
				}
			} else {
				array_unshift($joinedTables, ['table' => $firstTable, 'model' => $firstModel]);
				foreach ($model->getIdProperties() as $idProperty) {
					$column = $idProperty->getSerializationName();
					$on[] = [$column, $column];
				}
			}
		}
		return [$joinedTables, $on];
	}
	
	/**
	 * get unique alias
	 * 
	 * @return string
	 */
	private static function _getAlias() {
		return 't_'.self::$index++;
	}
	
	/**
	 * 
	 * @param array $joinedTables 
	 * @param array $groupColumns
	 * @param \stdClass $stdObject
	 * @param boolean $allowPrivateProperties
	 * @return SelectQuery
	 */
	private static function _setSubSelectQuery($joinedTables, $groupColumns, $stdObject, $allowPrivateProperties) {
		$mainTable   = $joinedTables[0]['table'];
		$selectQuery = new SelectQuery($mainTable);
		
		for ($i = 1; $i < count($joinedTables); $i++) {
			$joinTable = $joinedTables[$i];
			$selectQuery->join(SelectQuery::INNER_JOIN, $joinTable['table'], $joinTable['join_on']);
		}
			
		$lastTable = $joinedTables[count($joinedTables) - 1]['table'];
		$lastModel = $joinedTables[count($joinedTables) - 1]['model'];

		$selectQuery->setFocusOnMainTable();
		foreach ($groupColumns as $columns) {
			$mainTable->addSelectedColumn($columns[1]);
			$selectQuery->addGroup($columns[1]);
		}
		
		if (isset($stdObject->havingLogicalJunction)) {
			$having = HavingLogicalJunction::stdObjectToHavingLogicalJunction($stdObject->havingLogicalJunction, $mainTable, $lastTable, $lastModel, $allowPrivateProperties);
		} else {
			$table  = isset($stdObject->havingLiteral->function) && ($stdObject->havingLiteral->function == HavingLiteral::COUNT) ? $mainTable : $lastTable;
			$having = HavingLiteral::stdObjectToHavingLiteral($stdObject->havingLiteral, $table, $lastModel, $allowPrivateProperties);
		}
		$selectQuery->having($having);
		return $selectQuery;
	}
	
	/**
	 * 
	 * @param TableNode|string $leftTable
	 * @param TableNode|string $rightTable
	 * @param array $on
	 * @return OnLogicalJunction|OnLiteral
	 */
	private function _getJoinColumns($leftTable, $rightTable, $on) {
		if (count($on) == 1) {
			$onLiteral = new OnLiteral($leftTable, $on[0][0], Literal::EQUAL, $rightTable, $on[0][1]);
		} else {
			$onLiteral = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
			foreach ($on as $onLiteralArray) {
				$onSubLiteral = new OnLiteral($leftTable, $onLiteralArray[0], Literal::EQUAL, $rightTable, $onLiteralArray[1]);
				$onLiteral->addLiteral($onSubLiteral);
			}
		}
		return $onLiteral;
	}
	
}