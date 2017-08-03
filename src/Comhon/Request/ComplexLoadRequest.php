<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Request;

use Comhon\Database\DatabaseController;
use Comhon\Logic\Clause;
use Comhon\Logic\ClauseOptimizer;
use Comhon\Logic\Literal;
use Comhon\Database\SelectQuery;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Model;
use Comhon\Model\ModelArray;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Serialization\SqlTable;
use Comhon\Database\TableNode;
use Comhon\Database\OnLiteral;
use Comhon\Object\ObjectArray;
use Comhon\Interfacer\Interfacer;
use Comhon\Database\DbLiteral;
use Comhon\Exception\SerializationException;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\MalformedRequestException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Literal\MalformedLiteralException;
use Comhon\Exception\Literal\UnresolvableLiteralException;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\Literal\NotLinkableLiteralException;
use Comhon\Exception\Literal\IncompatibleLiteralSerializationException;
use Comhon\Exception\NotAllowedRequestException;

class ComplexLoadRequest extends ObjectLoadRequest {
	
	/**
	 * @var \Comhon\Database\SelectQuery select query to find and retrieve serialized comhon objects
	 */
	private $selectQuery;
	
	/** @var \Comhon\Model\Model[] models indexed by node id */
	private $modelByNodeId;
	
	/** @var \Comhon\Logic\Literal[] reusable literals */
	private $literalCollection = [];
	
	/** @var \Comhon\Logic\Formula filter to apply on query */
	private $filter;
	
	/** @var array ordering of retrieved serialized comhon objects */
	private $order = [];
	
	/** @var integer max length of retrieved serialized comhon objects */
	private $length;
	
	/** @var integer number of serialized comhon objects that will be skiped */
	private $offset;
	
	/** @var boolean define if literals have to opimized */
	private $optimizeLiterals = false;
	
	/** @var string database id of requested model */
	private $databaseId;
	
	/**
	 * 
	 * @param string $modelName
	 * @param boolean $private
	 * @throws \Exception
	 */
	public function __construct($modelName, $private = false) {
		parent::__construct($modelName, $private);
		if (!$this->model->hasSqlTableUnit()) {
			$types = [NotAllowedRequestException::INTERMEDIATE_REQUEST, NotAllowedRequestException::COMPLEXE_REQUEST];
			throw new NotAllowedRequestException($this->model, $types);
		}
		$database = $this->model->getSqlTableUnit()->getSettings()->getValue('database');
		if (!($database instanceof ObjectUnique)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		$this->databaseId = $database->getId();
	}
	
	/**
	 * set max length of retrieved comhon objects
	 * 
	 * @param integer $integer
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function setMaxLength($integer) {
		$this->length = $integer;
		return $this;
	}
	
	/**
	 * add ordering on retrieved comhon objects
	 * 
	 * @param string $propertyName
	 * @param string $type allowed values are [SelectQuery::DESC, SelectQuery::ASC]
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function addOrder($propertyName, $type = SelectQuery::ASC) {
		$type = strtoupper($type);
		if (!SelectQuery::isAllowedOrderType($type)) {
			throw new ArgumentException($type, SelectQuery::getAllowedOrderTypes(), 2);
		}
		$this->order[] = [$propertyName, $type];
		return $this;
	}
	
	/**
	 * add offset on retrieved comhon objects
	 * 
	 * indicates to pass specified number of rows before returning the remaining comhon objects
	 * 
	 * @param integer $integer
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function setOffset($integer) {
		$this->offset = $integer;
		return $this;
	}
	
	/*public function optimizeLiterals($boolean) {
		$this->optimizeLiterals = $boolean;
		return $this;
	}*/
	
	/**
	 * set logical junction to apply
	 * 
	 * logical junction permit to filter wanted cohmon object.
	 * replace logical junction or literal previously set
	 * 
	 * @param \Comhon\Logic\Formula $filter
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function setFilter($filter) {
		$this->filter = $filter;
		return $this;
	}
	
	/**
	 * build load request
	 *
	 * @param \stdClass $settings
	 * @param boolean $private
	 * @throws \Exception
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public static function buildObjectLoadRequest(\stdClass $settings, $private = false) {
		if (isset($settings->model)) {
			if (isset($settings->tree)) {
				throw new MalformedRequestException('request cannot have model property and tree property in same time');
			}
			$objectLoadRequest = new ComplexLoadRequest($settings->model, $private);
		} else if (isset($settings->tree) && isset($settings->tree->model)) {
			$objectLoadRequest = new ComplexLoadRequest($settings->tree->model, $private);
			$objectLoadRequest->importModelTree($settings->tree);
		} else {
			throw new MalformedRequestException('request doesn\'t have model');
		}
		if (isset($settings->literalCollection)) {
			$objectLoadRequest->importLiteralCollection($settings->literalCollection);
		}
		if (isset($settings->filter)) {
			if (isset($settings->filter->type)) { // clause
				$objectLoadRequest->importClause($settings->filter);
			} else { // literal
				$objectLoadRequest->importLiteral($settings->filter);
			}
		}
		if (isset($settings->maxLength)) {
			$objectLoadRequest->setMaxLength($settings->maxLength);
		}
		if (isset($settings->properties) && is_array($settings->properties)) {
			$objectLoadRequest->setPropertiesFilter($settings->properties);
		}
		if (isset($settings->offset)) {
			$objectLoadRequest->setOffset($settings->offset);
		}
		if (isset($settings->order)) {
			if (!is_array($settings->order)) {
				throw new MalformedRequestException('order parameter must be an array');
			}
			foreach ($settings->order as $order) {
				if (!isset($order->property)) {
					throw new MalformedRequestException('request order doesn\'t have property');
				}
				if (isset($order->type) && !SelectQuery::isAllowedOrderType($order->type)) {
					throw new MalformedRequestException("request order type '{$order->type}' is not allowed");
				}
				$objectLoadRequest->addOrder($order->property, isset($order->type) ? $order->type : SelectQuery::ASC);
			}
		}
		if (isset($settings->requestChildren)) {
			$objectLoadRequest->requestChildren($settings->requestChildren);
		}
		if (isset($settings->loadForeignProperties)) {
			$objectLoadRequest->loadForeignProperties($settings->loadForeignProperties);
		}
		return $objectLoadRequest;
	}
	
	/**
	 * import model tree
	 * 
	 * import requested model and links between differents models
	 * that are used in logical junction or literal (only for advanced request)
	 * 
	 * @param \stdClass $modelTree
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function importModelTree(\stdClass $modelTree) {
		if (!isset($modelTree->model)) {
			throw new MalformedRequestException('model tree doesn\'t have model');
		}
		if ($modelTree->model != $this->model->getName()) {
			throw new ComhonException('root model in model tree is not the same as model specified in constructor');
		}
		
		$tableNode = new TableNode($this->model->getSqlTableUnit()->getSettings()->getValue('name'), isset($modelTree->id) ? $modelTree->id : null);
		$this->selectQuery = new SelectQuery($tableNode);
		
		$this->modelByNodeId = [$tableNode->getExportName() => $this->model];
		
		$stack = [[$this->model, $tableNode, $modelTree]];
		while (!empty($stack)) {
			$lastElement    = array_pop($stack);
			$leftModel      = $lastElement[0];
			$leftTable      = $lastElement[1];
			
			if (isset($lastElement[2]->children) && is_array($lastElement[2]->children)) {
				foreach ($lastElement[2]->children as $childNode) {
					$rightTableAlias = isset($childNode->id) ? $childNode->id : null;
					$property        = $leftModel->getProperty($childNode->property, true);
					$joinedTable     = self::prepareJoinedTable($leftTable, $property, $this->databaseId, $rightTableAlias);
					
					$this->selectQuery->join(SelectQuery::LEFT_JOIN, $joinedTable['table'], $joinedTable['join_on']);
					$this->modelByNodeId[$joinedTable['table']->getExportName()] = $joinedTable['model'];
					$stack[] = [$property->getUniqueModel(), $joinedTable['table'], $childNode];
				}
			}
		}
		return $this;
	}
	
	/**
	 * import literal collection
	 * 
	 * literal collection contain a list of defined literals that are reusable in logical junction
	 * 
	 * @param \stdClass[] $stdObjectLiteralCollection
	 * @throws \Exception
	 */
	public function importLiteralCollection($stdObjectLiteralCollection) {
		if (is_null($this->modelByNodeId)) {
			throw new ComhonException('model tree must be set');
		}
		if (is_array($stdObjectLiteralCollection)) {
			foreach ($stdObjectLiteralCollection as $stdObjectLiteral) {
				if (!isset($stdObjectLiteral->node)) {
					throw new MalformedLiteralException($stdObjectLiteral);
				}
				if (!array_key_exists($stdObjectLiteral->node, $this->modelByNodeId)) {
					throw new MalformedRequestException('value of property \'node\' not defined in model tree : '.json_encode($stdObjectLiteral));
				}
				$this->addliteralToCollection(DbLiteral::stdObjectToLiteral($stdObjectLiteral, $this->modelByNodeId[$stdObjectLiteral->node], null, $this->selectQuery, $this->private));
			}
		}
	}
	
	/**
	 * add a literal into literals collection
	 * 
	 * @param \Comhon\Logic\Literal $literal
	 * @throws \Exception
	 */
	public function addliteralToCollection($literal) {
		if (!$literal->hasId()) {
			throw new MalformedRequestException('literal defined in collection must have an id');
		}
		if (array_key_exists($literal->getId(), $this->literalCollection)) {
			throw new MalformedRequestException("duplicated literal id '{$literal->getId()}' in literal collection");
		}
		$this->literalCollection[$literal->getId()] = $literal;
	}
	
	/**
	 * import logical junction
	 * 
	 * replace logical junction or literal previously set
	 * 
	 * @param \stdClass $stdObjectClause
	 */
	public function importClause(\stdClass $stdObjectClause) {
		if (is_null($this->modelByNodeId)) {
			$mainTableName = $this->model->getSqlTableUnit()->getSettings()->getValue('name');
			$mainTableNode = new TableNode($mainTableName);
			$this->selectQuery = new SelectQuery($mainTableNode);
			$this->modelByNodeId = [$mainTableName => $this->model];

			$litralsByModelName = [];
			$this->_getLiteralsByModelName($stdObjectClause, $mainTableName, $litralsByModelName);
			$this->_buildAndAddJoins($litralsByModelName);
		}
		$this->setFilter(Clause::stdObjectToClause($stdObjectClause, $this->modelByNodeId, $this->literalCollection, $this->selectQuery, $this->private));
	}
	
	/**
	 * import literal
	 * 
	 * replace logical junction or literal previously set
	 *
	 * @param \stdClass $stdObjectLiteral
	 */
	public function importLiteral($stdObjectLiteral) {
		if (is_null($this->modelByNodeId)) {
			$mainTableName = $this->model->getSqlTableUnit()->getSettings()->getValue('name');
			$mainTableNode = new TableNode($mainTableName);
			$this->selectQuery = new SelectQuery($mainTableNode);
			$this->modelByNodeId = [$mainTableName => $this->model];
			
			$litralsByModelName = [];
			$this->_getLiteralByModelName($stdObjectLiteral, $mainTableName, $litralsByModelName);
			$this->_buildAndAddJoins($litralsByModelName);
		}
		if (!isset($stdObjectLiteral->node)) {
			throw new MalformedLiteralException($stdObjectLiteral);
		}
		if (!array_key_exists($stdObjectLiteral->node, $this->modelByNodeId)) {
			throw new MalformedRequestException('value of property \'node\' not defined in model tree : '.json_encode($stdObjectLiteral));
		}
		$this->setFilter(DbLiteral::stdObjectToLiteral($stdObjectLiteral, $this->modelByNodeId[$stdObjectLiteral->node], $this->literalCollection, $this->selectQuery, $this->private));
	}
	
	/**
	 * finalize request (must be called before query execution)
	 * 
	 * @throws \Exception
	 */
	private function _finalize() {
		if (is_null($this->selectQuery)) {
			throw new ComhonException('query not initialized');
		}
		if (!is_null($this->filter)) {
			if ($this->optimizeLiterals) {
				$this->filter = ClauseOptimizer::optimizeLiterals($this->filter);
			}
			$this->selectQuery->where($this->filter);
		}
		$this->selectQuery->limit($this->length)->offset($this->offset);
		$this->selectQuery->setFocusOnMainTable();
		$this->_addColumns();
		$this->_addGroupedColumns();
		$this->_addOrderColumns();
	}
	
	/**
	 * execute resquest and return resulting object
	 * 
	 * @return \Comhon\Object\ObjectArray
	 */
	public function execute() {
		$this->_finalize();
		$sqlTable = $this->model->getSqlTableUnit()->getSettings();
		$sqlTable->loadValue('database');
		$dbInstance = DatabaseController::getInstanceWithDataBaseObject($sqlTable->getValue('database'));
		$rows = $dbInstance->executeSelectQuery($this->selectQuery);
		SqlTable::castStringifiedColumns($rows, $this->model);
		
		return $this->_buildObjectsWithRows($rows);
	}
	
	/**
	 * export select query built from request settings
	 * 
	 * @return \Comhon\Database\SelectQuery
	 */
	public function exportQuery() {
		$this->_finalize();
		return $this->selectQuery;
	}
	
	/**
	 * populate $litralsByModelName parameter (passed by reference)
	 * 
	 * regroup literals by their associated model
	 * 
	 * @param \stdClass $stdObjectClause
	 * @param string $mainTableName
	 * @param array $litralsByModelName
	 */
	private function _getLiteralsByModelName($stdObjectClause, $mainTableName, &$litralsByModelName) {
		if (isset($stdObjectClause->elements)) {
			foreach ($stdObjectClause->elements as $stdObjectElement) {
				if (isset($stdObjectElement->type)) { // clause
					$this->_getLiteralsByModelName($stdObjectElement, $mainTableName, $litralsByModelName);
				} else { // literal
					$this->_getLiteralByModelName($stdObjectElement, $mainTableName, $litralsByModelName);
				}
			}
		}
	}
	
	/**
	 * populate $litralsByModelName parameter (passed by reference)
	 *
	 * put literal in its associated model key
	 *
	 * @param \stdClass $literal
	 * @param string $mainTableName
	 * @param array $litralsByModelName
	 * @throws \Exception
	 */
	private function _getLiteralByModelName($literal, $mainTableName, &$litralsByModelName) {
		if (!isset($literal->model)) {
			throw new MalformedLiteralException($literal);
		}
		ModelManager::getInstance()->getInstanceModel($literal->model); // verify if model exists
		if (!array_key_exists($literal->model, $litralsByModelName)) {
			$litralsByModelName[$literal->model] = [];
		}
		if ($literal->model == $this->model->getName()) {
			$literal->node = $mainTableName;
		}
		else {
			$litralsByModelName[$literal->model][] = $literal;
		}
	}
	
	/**
	 * join needed table to select query (only for intermediate request)
	 * 
	 * in intermediate request dependencies between tables are not specified
	 * so we have to find dependencies according literals request
	 * 
	 * @param array $litralsByModelName
	 * @throws \Exception
	 */
	private function _buildAndAddJoins($litralsByModelName) {
		if ((empty($litralsByModelName)) || ((count($litralsByModelName) == 1) && array_key_exists($this->model->getName(), $litralsByModelName))) {
			return;
		}
		$temporaryLeftJoins = [];
		$stackVisitedModels = [];
		$arrayVisitedModels = [];
		$stack              = [];
		
		$this->_extendsStacks($this->model, $litralsByModelName, $stack, $stackVisitedModels, $arrayVisitedModels);
	
		// Depth-first search to build all left joins
		while (!empty($stack)) {
			if ($stack[count($stack) - 1]['current'] != -1) {
				array_pop($temporaryLeftJoins);
				$modelName = array_pop($stackVisitedModels);
				$arrayVisitedModels[$modelName] -= 1;
			}
			$stack[count($stack) - 1]['current']++;
			if ($stack[count($stack) - 1]['current'] < count($stack[count($stack) - 1]['properties'])) {
				$stackIndex     = count($stack) - 1;
				$rightProperty  = $stack[$stackIndex]['properties'][$stack[$stackIndex]['current']];
				$rightModel     = $rightProperty->getUniqueModel();
				$rightModelName = $rightModel->getName();
				
				$higherRightModelName = $rightModelName;
				$model = $rightModel->getParent();
				while (!is_null($model) && $model->getSerializationSettings() === $rightModel->getSerializationSettings()) {
					$higherRightModelName = $model->getName();
					$model = $model->getParent();
				}
				
				if (array_key_exists($higherRightModelName, $arrayVisitedModels) && ($arrayVisitedModels[$higherRightModelName] > 0)) {
					$stackVisitedModels[] = $higherRightModelName;
					$arrayVisitedModels[$higherRightModelName] += 1;
					$temporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $rightModel is in literals ($litralsByModelName)
				$leftModel = $stack[$stackIndex]['left_model'];
				$temporaryLeftJoins[] = self::prepareJoinedTable($leftModel->getSqlTableUnit()->getSettings()->getValue('name'), $rightProperty, $this->databaseId);
				if (array_key_exists($rightModelName, $litralsByModelName)) {
					$this->_joinTables($temporaryLeftJoins, $litralsByModelName[$rightModelName]);
					$temporaryLeftJoins = [];
				}
				// add serializable properties to stack
				$this->_extendsStacks($rightModel, $litralsByModelName, $stack, $stackVisitedModels, $arrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (empty($stack[count($stack) - 1]['properties'])) {
					array_pop($stack);
				}
			}
			else {
				array_pop($stack);
				array_pop($temporaryLeftJoins);
			}
		}
		foreach ($litralsByModelName as $literals) {
			foreach ($literals as $literal) {
				if (!isset($literal->node)) {
					throw new NotLinkableLiteralException($this->model, $literal);
				}
			}
		}
	}
	
	/**
	 * join specified tables to select query
	 * 
	 * @param array $joinedTables
	 * @param \stdClass[] $literals
	 */
	private function _joinTables($joinedTables, $literals) {
		if (!empty($literals)) {
			foreach ($joinedTables as $joinedTable) {
				$this->selectQuery->join(SelectQuery::LEFT_JOIN, $joinedTable['table'], $joinedTable['join_on']);
				$this->modelByNodeId[$joinedTable['table']->getExportName()] = $joinedTable['model'];
			}
			$nodeId = $joinedTables[count($joinedTables) - 1]['table']->getExportName();
			foreach ($literals as $literal) {
				$literal->node = $nodeId;
			}
		}
	}
	
	/**
	 * extends models stack
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param array $literalsByModelName
	 * @param array $stack
	 * @param array $stackVisitedModels
	 * @param array $arrayVisitedModels
	 * @throws \Exception
	 */
	private function _extendsStacks(Model $model, $literalsByModelName, &$stack, &$stackVisitedModels, &$arrayVisitedModels) {
		if (array_key_exists($model->getName(), $arrayVisitedModels) && array_key_exists($model->getName(), $literalsByModelName)) {
			throw new UnresolvableLiteralException($model);
		}
		
		$extendablesProperties = [];
		foreach ($model->getForeignSerializableProperties('sqlTable') as $property) {
			$database = $property->getUniqueModel()->getSerialization()->getSettings()->getValue('database');
			if (!($database instanceof ObjectUnique)) {
				throw new SerializationException('not valid serialization settings, database information is missing');
			}
			if ($database->getId() === $this->databaseId) {
				$extendablesProperties[] = $property;
			}
		}
		
		$stack[] = [
			'left_model' => $model,
			'properties' => $extendablesProperties,
			'current'    => -1
		];
		
		$higherRightModelName = $model->getName();
		$parentModel = $model->getParent();
		while (!is_null($parentModel) && $parentModel->getSerializationSettings() === $model->getSerializationSettings()) {
			$higherRightModelName = $parentModel->getName();
			$parentModel = $parentModel->getParent();
		}
		
		$stackVisitedModels[] = $higherRightModelName;
		$arrayVisitedModels[$higherRightModelName] = array_key_exists($higherRightModelName, $arrayVisitedModels) ? $arrayVisitedModels[$higherRightModelName] + 1 : 1;
	}
	
	/**
	 * prepare join that will be add to select query
	 * 
	 * @param string|TableNode $leftTable
	 * @param \Comhon\Model\Property\Property $rightProperty
	 * @param string $databaseId
	 * @param string $rightAliasTable
	 * @param boolean $selectAllColumns
	 * @throws \Exception
	 * @return ['model' => \Comhon\Model\Model, 'table' => \Comhon\Database\TableNode, 'join_on' => \Comhon\Database\OnLiteral]
	 */
	public static function prepareJoinedTable($leftTable, $rightProperty, $databaseId, $rightAliasTable = null, $selectAllColumns = false) {
		if (!($rightProperty instanceof ForeignProperty) || !$rightProperty->getUniqueModel()->hasSqlTableUnit()) {
			throw new IncompatibleLiteralSerializationException($rightProperty);
		}
		$database = $rightProperty->getUniqueModel()->getSerialization()->getSettings()->getValue('database');
		if (!($database instanceof ObjectUnique)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		if ($database->getId() !== $databaseId) {
			throw new IncompatibleLiteralSerializationException($rightProperty);
		}
		$rightModel = $rightProperty->getUniqueModel();
		$rightTable = new TableNode($rightModel->getSqlTableUnit()->getSettings()->getValue('name'), $rightAliasTable, $selectAllColumns);
		
		if ($rightProperty->isAggregation()) {
			$disJunction = [];
			foreach ($rightProperty->getAggregationProperties() as $aggregationProperty) {
				$rightForeignProperty = $rightModel->getProperty($aggregationProperty, true);
				
				if ($rightForeignProperty->hasMultipleSerializationNames()) {
					$on = new Clause(Clause::CONJUNCTION);
					foreach ($rightForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
						$on->addLiteral(new OnLiteral(
							$leftTable,
							$idProperty->getSerializationName(),
							Literal::EQUAL,
							$rightTable,
							$serializationName
						));
					}
					$disJunction[] = $on;
				} else {
					$disJunction[] = new OnLiteral(
						$leftTable,
						$rightForeignProperty->getUniqueModel()->getFirstIdProperty()->getSerializationName(),
						Literal::EQUAL,
						$rightTable,
						$rightForeignProperty->getSerializationName()
					);
				}
			}
			if (count($disJunction) == 1) {
				$on = $disJunction[0];
			} else {
				$on = new Clause(Clause::DISJUNCTION);
				foreach ($disJunction as $onElement) {
					if ($onElement instanceof Literal) {
						$on->addLiteral($onElement);
					} else {
						$on->addClause($onElement);
					}
				}
			}
		}else {
			if ($rightProperty->hasMultipleSerializationNames()) {
				$on = new Clause(Clause::CONJUNCTION);
				foreach ($rightProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
					$on->addLiteral(new OnLiteral(
						$leftTable,
						$serializationName,
						Literal::EQUAL, 
						$rightTable,
						$idProperty->getSerializationName()
					));
				}
			} else {
				$on = new OnLiteral(
					$leftTable, 
					$rightProperty->getSerializationName(), 
					Literal::EQUAL, 
					$rightTable, 
					$rightModel->getFirstIdProperty()->getSerializationName()
				);
			}
		}
		return [
			'model'   => $rightModel,
			'table'   => $rightTable,
			'join_on' => $on
		];
	}
	
	/**
	 * add select columns to select query
	 */
	private function _addColumns() {
		
		if (empty($this->propertiesFilter)) {
			$this->selectQuery->getMainTable()->selectAllColumns(true);
		}
		else {
			$selectedColumns = [];
			foreach ($this->propertiesFilter as $propertyName) {
				$selectedColumns[] = $this->model->getProperty($propertyName, true)->getSerializationName();
			}
			$mainTable = $this->selectQuery->getMainTable();
			foreach ($selectedColumns as $column) {
				$mainTable->addSelectedColumn($column);
			}
		}
	}
	
	/**
	 * add group on select query
	 */
	private function _addGroupedColumns() {
		$this->selectQuery->resetGroupColumns();
		foreach ($this->model->getIdProperties() as $property) {
			$this->selectQuery->addGroup($property->getSerializationName());
		}
	}
	
	/**
	 * add order on select query
	 */
	private function _addOrderColumns() {
		$this->selectQuery->resetOrderColumns();
		foreach ($this->order as $order) {
			$this->selectQuery->addOrder($this->model->getProperty($order[0], true)->getSerializationName(), $order[1]);
		}
	}
	
	/**
	 * build comhon objects from rows retrieved from database
	 * 
	 * @param array $rows
	 * @return \Comhon\Object\ObjectArray
	 */
	private function _buildObjectsWithRows($rows) {
		$sqlTableUnit = $this->model->getSqlTableUnit();
		
		if (!is_null($sqlTableUnit->getInheritanceKey())) {
			foreach ($rows as &$row) {
				$model = $sqlTableUnit->getInheritedModel($row, $this->model);
				if ($model!== $this->model) {
					$row[Interfacer::INHERITANCE_KEY] = $model->getName();
				}
			}
		}
		$modelArray = new ModelArray($this->model, $this->model->getName());
		$objectArray = $modelArray->import($rows, SqlTable::getInterfacer());
		
		return $this->_completeObject($objectArray);
	}
	
}