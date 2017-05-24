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
	
	const EQUAL      = '=';
	const SUPP       = '>';
	const INF        = '<';
	const SUPP_EQUAL = '>=';
	const INF_EQUAL  = '<=';
	const DIFF       = '<>';
	
	private static $index = 0;

	protected $id;
	protected $table;
	protected $column;
	protected $operator;
	protected $value;
	
	protected static $acceptedOperators = [
		self::EQUAL      => null,
		self::SUPP       => null,
		self::INF        => null,
		self::SUPP_EQUAL => null,
		self::INF_EQUAL  => null,
		self::DIFF       => null
	];
	
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
	 * @param unknown $table
	 * @param unknown $column 
	 * @param unknown $operator
	 * @param unknown $value could be :
	 * - null
	 * - a string
	 * - a number
	 * - an array with null or string or number values
	 * @param string $modelName
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
		if (!array_key_exists($this->operator, self::$acceptedOperators)) {
			throw new \Exception('operator \''.$this->operator.'\' doesn\'t exists');
		}
		if (is_null($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new \Exception('literal with operator \''.$this->operator.'\' can\'t have null value');
		}
		if (is_array($this->value) && ($this->operator != '=') && ($this->operator != '<>')) {
			throw new \Exception('literal with operator \''.$this->operator.'\' can\'t have array value');
		}
	}

	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function hasId() {
		return !is_null($this->id);
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function getPropertyName() {
		return $this->column;
	}
	
	public function getOperator() {
		return $this->operator;
	}
	
	public function reverseOperator() {
		$this->operator = self::$oppositeOperator[$this->operator];
	}
	
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * @param array $values
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
	 * can't be used to populate a database query
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
	 * @param stdClass $stdObject
	 * @param Model $mainModel (reference is specified to stay compatible with inherited function, there's probably a better way...)
	 * @param Literal[] $literalCollection
	 * @param SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToLiteral($stdObject, &$mainModel, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
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
	 * @param MainModel $model
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
				foreach ($model->getSerializationIds() as $column) {
					$on[] = [$column, $column];
				}
			}
		}
		return [$joinedTables, $on];
	}
	
	private static function _getAlias() {
		return 't_'.self::$index++;
	}
	
	/**
	 * 
	 * @param TableNode $mainTable
	 * @param Model $mainModel
	 * @param [] $joinedTables
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

		$selectQuery->setMainTableAsCurrentTable();
		foreach ($groupColumns as $columns) {
			$mainTable->addSelectedColumn($columns[1]);
			$selectQuery->addGroupColumn($columns[1]);
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
	 * @param string $leftTable
	 * @param string $rightTable
	 * @param [] $on
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