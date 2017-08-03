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
use Comhon\Logic\Clause;
use Comhon\Logic\Literal;
use Comhon\Exception\Literal\LiteralNotFoundException;
use Comhon\Exception\Literal\LiteralPropertyAggregationException;
use Comhon\Exception\PropertyVisibilityException;
use Comhon\Exception\Literal\MalformedLiteralException;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\SerializationException;

abstract class DbLiteral extends Literal {
	
	private static $index = 0;

	/** @var TableNode|string */
	protected $table;
	
	/** @var string */
	protected $column;
	
	/**
	 * @param TableNode|string $table
	 * @param string $column
	 * @param string $operator
	 */
	public function __construct($table, $column, $operator) {
		parent::__construct($operator);
		$this->table     = $table;
		$this->column    = $column;
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
	 * 
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * build instance of Literal 
	 * 
	 * @param \stdClass $stdObject
	 * @param \Comhon\Model\MainModel $mainModel
	 * @param DbLiteral[] $literalCollection used if $stdObject contain only an id that reference literal in collection
	 * @param SelectQuery $selectQuery
	 * @param boolean $allowPrivateProperties
	 * @throws \Exception
	 * @return DbLiteral
	 */
	public static function stdObjectToLiteral($stdObject, $mainModel, $literalCollection = null, $selectQuery = null, $allowPrivateProperties = true) {
		if (isset($stdObject->id) && !is_null($literalCollection)) {
			if (!array_key_exists($stdObject->id, $literalCollection)) {
				throw new LiteralNotFoundException($stdObject->id);
			}
			return $literalCollection[$stdObject->id];
		}
		self::_verifStdObject($stdObject);
		$table = $stdObject->node;
		
		if (isset($stdObject->queue)) {
			list($joinedTables, $on) = self::_getJoinedTablesFromQueue($mainModel, $stdObject->queue, $allowPrivateProperties);
			$subSelectQuery = self::_setSubSelectQuery($joinedTables, $on, $stdObject, $allowPrivateProperties);
			$rigthTable  = new TableNode($subSelectQuery, self::_getAlias(), false);
			
			if (!is_null($selectQuery)) {
				$selectQuery->join(SelectQuery::LEFT_JOIN, $rigthTable, self::_getJoinColumns($table, $rigthTable->getExportName(), $on));
			}
			if (count($on) == 1) {
				$literal = new SimpleDbLiteral($rigthTable, $on[0][1], Literal::DIFF, null);
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
				throw new LiteralPropertyAggregationException($property->getName());
			}
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new PropertyVisibilityException($property->getName());
			}
			$literal  = new SimpleDbLiteral($table, $property->getSerializationName(), $stdObject->operator, $stdObject->value);
		}
		if (isset($stdObject->id)) {
			$literal->setId($stdObject->id);
		}
		return $literal;
	}
	
	/**
	 * verify if given object has expected format
	 * 
	 * @param \stdClass $stdObject
	 * @throws \Exception
	 */
	private static function _verifStdObject($stdObject) {
		if (!is_object($stdObject) || !isset($stdObject->node)) {
			throw new MalformedLiteralException($stdObject);
		}
		if (isset($stdObject->queue)) {
			if (!isset($stdObject->having) || !is_object($stdObject->queue)) {
				throw new MalformedLiteralException($stdObject);
			}
		} elseif (
			!isset($stdObject->operator)
			|| !array_key_exists($stdObject->operator, self::$allowedOperators)
			|| !property_exists($stdObject, 'value')
			|| is_object($stdObject->value)
			|| !isset($stdObject->property)
			|| !is_string($stdObject->property)
			|| (is_null($stdObject->value) && ($stdObject->operator != self::EQUAL) && ($stdObject->operator != self::DIFF))
			|| (is_array($stdObject->value) && ($stdObject->operator != self::EQUAL) && ($stdObject->operator != self::DIFF))
		) {
			throw new MalformedLiteralException($stdObject);
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
		
		if (!$firstModel->hasSqlTableUnit()) {
			throw new SerializationException('resquested model '.$firstModel->getName().' must have a database serialization');
		}
		$database = $firstModel->getSqlTableUnit()->getSettings()->getValue('database');
		if (!($database instanceof ObjectUnique)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		$databaseId = $database->getId();
		
		while (!is_null($currentNode)) {
			if (!is_object($currentNode) || !isset($currentNode->property)) {
				throw new MalformedLiteralException($queue);
			}
			$property = $leftModel->getProperty($currentNode->property, true);
			if (!$allowPrivateProperties && $property->isPrivate()) {
				throw new PropertyVisibilityException($property->getName());
			}
			$leftJoin       = ComplexLoadRequest::prepareJoinedTable($leftTable, $property, $databaseId, self::_getAlias());
			$joinedTables[] = $leftJoin;
			
			
			$leftModel   = $leftJoin['model'];
			$leftTable   = $leftJoin['table'];
			$currentNode = isset($currentNode->child) ? $currentNode->child : null;
		}
		if (!is_null($firstNode)) {
			$on = [];
			if (!($joinedTables[0]['join_on'] instanceof Clause) || $joinedTables[0]['join_on']->getType() !== Clause::DISJUNCTION) {
				$firstJoinedTable = $joinedTables[0];
				if ($firstJoinedTable['join_on'] instanceof Clause) {
					foreach ($firstJoinedTable['join_on']->getElements() as $literal) {
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
		
		if (isset($stdObject->having->type)) {
			$having = HavingClause::stdObjectToHavingClause($stdObject->having, $mainTable, $lastTable, $lastModel, $allowPrivateProperties);
		} else {
			$table  = isset($stdObject->having->function) && ($stdObject->having->function == HavingLiteral::COUNT) ? $mainTable : $lastTable;
			$having = HavingLiteral::stdObjectToHavingLiteral($stdObject->having, $table, $lastModel, $allowPrivateProperties);
		}
		$selectQuery->having($having);
		return $selectQuery;
	}
	
	/**
	 * 
	 * @param TableNode|string $leftTable
	 * @param TableNode|string $rightTable
	 * @param array $on
	 * @return \Comhon\Logic\Clause|OnLiteral
	 */
	private function _getJoinColumns($leftTable, $rightTable, $on) {
		if (count($on) == 1) {
			$onLiteral = new OnLiteral($leftTable, $on[0][0], Literal::EQUAL, $rightTable, $on[0][1]);
		} else {
			$onLiteral = new Clause(Clause::CONJUNCTION);
			foreach ($on as $onLiteralArray) {
				$onSubLiteral = new OnLiteral($leftTable, $onLiteralArray[0], Literal::EQUAL, $rightTable, $onLiteralArray[1]);
				$onLiteral->addLiteral($onSubLiteral);
			}
		}
		return $onLiteral;
	}
	
	/**
	 * get unique alias
	 *
	 * @return string
	 */
	private static function _getAlias() {
		return 't_'.self::$index++;
	}
	
}