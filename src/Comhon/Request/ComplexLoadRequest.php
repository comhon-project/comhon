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

use Comhon\Database\DatabaseHandler;
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
use Comhon\Database\DbLiteral;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Literal\UnresolvableLiteralException;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Literal\NotLinkableLiteralException;
use Comhon\Exception\Literal\IncompatibleLiteralSerializationException;
use Comhon\Exception\Request\NotAllowedRequestException;
use Comhon\Database\SimpleDbLiteral;
use Comhon\Object\ComhonObject;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Visitor\ObjectValidator;

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
	private $limit;
	
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
		if (!$this->model->hasSqlTableSerialization()) {
			$types = [NotAllowedRequestException::INTERMEDIATE_REQUEST, NotAllowedRequestException::COMPLEXE_REQUEST];
			throw new NotAllowedRequestException($this->model, $types);
		}
		$database = $this->model->getSqlTableSettings()->getValue('database');
		if (!($database instanceof UniqueObject)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		$this->databaseId = $database->getId();
	}
	
	/**
	 * set limit of retrieved comhon objects
	 * 
	 * @param integer $integer
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public function setLimit($integer) {
		$this->limit = $integer;
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
		$this->model->getProperty($propertyName, true); // verify property existence
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
	 * build load request
	 *
	 * @param \stdClass|array|\SimpleXMLElement|\DOMNode|\Comhon\Object\UniqueObject $request
	 * @param boolean $private
	 * @throws \Exception
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	public static function build($request, $private = false) {
		if ($request instanceof UniqueObject) {
			if (!$request->getModel()->isInheritedFrom(ModelManager::getInstance()->getInstanceModel('Comhon\Request'))) {
				$expected = ModelManager::getInstance()->getInstanceModel('Comhon\Request')->getObjectInstance(false)->getComhonClass();
				throw new ArgumentException($request, $expected, 1);
			}
			$visitor = new ObjectValidator();
			$visitor->execute($request, [ObjectValidator::VERIF_REFERENCES => true, ObjectValidator::VERIF_FOREIGN_ID => true]);
		} else {
			if ($request instanceof \stdClass) {
				$interfacer = new StdObjectInterfacer();
			} elseif (is_array($request)) {
				$interfacer = new AssocArrayInterfacer();
			} elseif (($request instanceof \SimpleXMLElement) || $request instanceof \DOMNode) {
				$interfacer = new XMLInterfacer();
			} else {
				$expected = ['\stdClass', 'array', '\SimpleXMLElement', '\DOMNode'];
				throw new ArgumentException($request, $expected, 1);
			}
			$request = $interfacer->import($request, ModelManager::getInstance()->getInstanceModel('Comhon\Request'));
		}
		
		return self::_build($request, $private);
	}
	
	/**
	 * build load request
	 *
	 * @param \Comhon\Object\UniqueObject $request
	 * @param boolean $private
	 * @throws \Exception
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	private static function _build(UniqueObject $request, $private = false) {
		if ($request->getModel()->getName() === 'Comhon\Request\Intermediate') {
			$request = self::_intermediateToComplexRequest($request);
		}
		$objectLoadRequest = new ComplexLoadRequest($request->getValue('tree')->getValue('model'), $private);
		$objectLoadRequest->_importModelTree($request->getValue('tree'));
		if ($request->hasValue('filter')) {
			$objectLoadRequest->_importFilter($request->getValue('filter'));
		}
		if ($request->hasValue('limit')) {
			$objectLoadRequest->setLimit($request->getValue('limit'));
		}
		if ($request->hasValue('properties')) {
			$objectLoadRequest->setPropertiesFilter($request->getValue('properties')->getValues());
		}
		if ($request->hasValue('offset')) {
			$objectLoadRequest->setOffset($request->getValue('offset'));
		}
		if ($request->hasValue('order')) {
			foreach ($request->getValue('order') as $orderElement) {
				$objectLoadRequest->addOrder($orderElement->getValue('property'), $orderElement->getValue('type'));
			}
		}
		return $objectLoadRequest;
	}
	
	/**
	 * transform intermediate request to complex request
	 *
	 * @param ComhonObject $request
	 * @throws ArgumentException
	 * @throws NotLinkableLiteralException
	 * @return \Comhon\Object\UniqueObject
	 */
	public static function intermediateToComplexRequest(ComhonObject $request) {
		if ($request->getModel()->getName() != 'Comhon\Request\Intermediate') {
			$expected = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate')->getObjectInstance(false)->getComhonClass();
			throw new ArgumentException($request, $expected, 1);
		}
		$visitor = new ObjectValidator();
		$visitor->execute($request, [ObjectValidator::VERIF_REFERENCES => true, ObjectValidator::VERIF_FOREIGN_ID => true]);
		
		return self::_intermediateToComplexRequest($request);
	}
	
	/**
	 * transform intermediate request to complex request
	 * 
	 * @param ComhonObject $request
	 * @throws ArgumentException
	 * @throws NotLinkableLiteralException
	 * @return \Comhon\Object\UniqueObject
	 */
	private static function _intermediateToComplexRequest(ComhonObject $request) {
		if ($request->hasValue('filter')) {
			$maxId = 0;
			$model = ModelManager::getInstance()->getInstanceModel($request->getValue('root')->getValue('model'));
			$literalsByModelName = self::_getLiteralsByModelName($request->getValue('filter'), $maxId);
			$key = ObjectCollection::getModelKey($model)->getName();
			if (count($literalsByModelName) == 1 && array_key_exists($key, $literalsByModelName)) {
				$root = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Root')->getObjectInstance(false);
				$root->setId($request->getValue('root')->getId());
				$root->setValue('model', $request->getValue('root')->getValue('model'));
				$root->setIsLoaded(true);
			} else {
				$root = self::_buildTree($model, $literalsByModelName, $maxId);
			}
			if (isset($literalsByModelName[$key])) {
				/** @var \Comhon\Object\UniqueObject $object */
				foreach ($literalsByModelName[$key] as $object) {
					$object->setvalue('node', $root);
				}
				unset($literalsByModelName[$key]);
			}
			if (count($literalsByModelName) > 0) {
				$literals = current($literalsByModelName);
				throw new NotLinkableLiteralException($model, $literals[0]);
			}
		} else {
			$root = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Root')->getObjectInstance(false);
			$root->setId($request->getValue('root')->getId());
			$root->setValue('model', $request->getValue('root')->getValue('model'));
			$root->setIsLoaded(true);
		}
		$values = $request->getValues();
		unset($values['root']);
		unset($values['models']);
		
		$complexRequest = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex')->getObjectInstance(false);
		$complexRequest->setValue('tree', $root);
		foreach ($values as $name => $value) {
			$complexRequest->setValue($name, $value);
		}
		$complexRequest->setIsLoaded(true);
		
		return $complexRequest;
	}
	
	private function _getLiteralsByModelName(ComhonObject $filter, &$maxId) {
		$collectionMap = ObjectCollection::build($filter, true, true)->getMap();
		$literalsByModelName = [];
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal');
		
		/** @var \Comhon\Object\UniqueObject $object */
		foreach ($collectionMap['Comhon\Logic\Simple\Formula'] as $object) {
			if ($object->getModel()->isInheritedFrom($literalModel) || $object->getModel()->getName() == 'Comhon\Logic\Simple\Having') {
				$modelName = $object->getValue('node')->getValue('model');
				$key = ObjectCollection::getModelKey(ModelManager::getInstance()->getInstanceModel($modelName))->getName();
				if (!array_key_exists($key, $literalsByModelName)) {
					$literalsByModelName[$key] = [];
				}
				$literalsByModelName[$key][] = $object;
				$maxId = max($maxId, $object->getValue('node')->getId());
			}
		}
		
		return $literalsByModelName;
	}
	
	private function _buildTree(Model $model, array &$literalsByModelName, &$maxId, $propertyName = null, &$visited = [], &$visitedStack = [], $databaseId = null) {
		$key = ObjectCollection::getModelKey(ModelManager::getInstance()->getInstanceModel($model->getName()))->getName();
		if (array_key_exists($key, $visitedStack)) {
			return;
		}
		if (array_key_exists($key, $visited)) {
			if (!is_null($visited[$key])) {
				throw new UnresolvableLiteralException(ModelManager::getInstance()->getInstanceModel($visited[$key]));
			}
			return;
		}
		$visitedStack[$key] = null;
		$visited[$key] = null;
		$node = null;
		
		if (is_null($propertyName)) {
			if ($model->getSerialization() && $model->getSerialization()->getSerializationUnit() instanceof SqlTable) {
				$database = $model->getSerializationSettings()->getValue('database');
				if (!($database instanceof UniqueObject)) {
					throw new SerializationException('not valid serialization settings, database information is missing');
				}
				$databaseId = $database->getId();
			}
			$node = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Root')->getObjectInstance(false);
			$node->setValue('model', $model->getName());
			$node->initValue('nodes');
			$id = array_key_exists($key, $literalsByModelName) 
				? $literalsByModelName[$key][0]->getValue('node')->getId() : ++$maxId;
			$node->setId($id);
			$node->setIsLoaded(true);
		}
		elseif (array_key_exists($key, $literalsByModelName)) {
			$node = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Node')->getObjectInstance(false);
			$node->setValue('property', $propertyName);
			$node->initValue('nodes');
			$node->setId($literalsByModelName[$key][0]->getValue('node')->getId());
			$node->setIsLoaded(true);
			/** @var \Comhon\Object\UniqueObject $object */
			foreach ($literalsByModelName[$key] as $object) {
				$object->setvalue('node', $node);
			}
			unset($literalsByModelName[$key]);
			
			foreach ($visitedStack as $stackKey => $value) {
				$visited[$stackKey] = $key;
			}
		}
		
		foreach ($model->getForeignSerializableProperties('Comhon\SqlTable') as $property) {
			if (!is_null($databaseId)) {
				$database = $property->getUniqueModel()->getSqlTableSettings()->getValue('database');
				if (!($database instanceof UniqueObject)) {
					throw new SerializationException('not valid serialization settings, database information is missing');
				}
				if ($database->getId() !== $databaseId) {
					continue;
				}
			}
			$propertyNode = self::_buildTree($property->getUniqueModel(), $literalsByModelName, $maxId, $property->getName(), $visited, $visitedStack, $databaseId);
			if (!is_null($propertyNode)) {
				if (is_null($node)) {
					$node = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Node')->getObjectInstance(false);
					$node->setValue('property', $propertyName);
					$node->initValue('nodes');
					$node->setId(++$maxId);
					$node->setIsLoaded(true);
				}
				$node->getValue('nodes')->pushValue($propertyNode);
			}
		}
		unset($visitedStack[$key]);
		
		return $node;
	}
	
	/**
	 * get table alias name according node id. 
	 * 
	 * @param \Comhon\Object\UniqueObject $node model object must be a 'Comhon\Model'
	 * @return string
	 */
	public static function getTableAliasWithModelNode(UniqueObject $node) {
		return 't_' . $node->getValue('id');
	}
	
	/**
	 * import tree
	 *
	 * import requested model and links between differents models
	 * that are used in logical junction or literal (only for advanced request)
	 *
	 * @param \stdClass $tree
	 * @return \Comhon\Request\ComplexLoadRequest
	 */
	private function _importModelTree(UniqueObject $tree) {
		$tableNode = new TableNode($this->model->getSqlTableSettings()->getValue('name'), self::getTableAliasWithModelNode($tree));
		$this->selectQuery = new SelectQuery($tableNode);
		
		$this->modelByNodeId = [$tree->getId() => $this->model];
		
		$stack = [[$this->model, $tableNode, $tree]];
		while (!empty($stack)) {
			$lastElement = array_pop($stack);
			/** @var Model $leftModel */
			$leftModel = $lastElement[0];
			$leftTable = $lastElement[1];
			/** @var UniqueObject $treeNode */
			$treeNode = $lastElement[2];
			
			if ($treeNode->hasValue('nodes')) {
				foreach ($treeNode->getValue('nodes') as $childNode) {
					$rightTableAlias = self::getTableAliasWithModelNode($childNode);
					$property        = $leftModel->getProperty($childNode->getValue('property'), true);
					$joinedTable     = self::prepareJoinedTable($leftTable, $property, $this->databaseId, $rightTableAlias);
					
					$this->selectQuery->join(SelectQuery::LEFT_JOIN, $joinedTable['table'], $joinedTable['join_on']);
					$this->modelByNodeId[$childNode->getId()] = $joinedTable['model'];
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
	 * @param \Comhon\Object\UniqueObject $filter
	 * @throws \Exception
	 */
	private function _importFilter(UniqueObject $filter) {
		if ($filter->getModel()->getName() == 'Comhon\Logic\Simple\Clause') { // clause
			$this->filter = Clause::build(
				$filter, 
				$this->modelByNodeId, 
				$this->selectQuery, 
				$this->private
			);
		} else { // literal
			$this->filter = DbLiteral::build(
				$filter, 
				$this->modelByNodeId[$filter->getValue('node')->getId()], 
				$this->selectQuery, 
				$this->private
			);
		}
	}
	
	/**
	 * finalize request (must be called before query execution)
	 * 
	 * @var bool $isCount
	 * @throws \Exception
	 */
	private function _finalize($isCount = false) {
		if (is_null($this->selectQuery)) {
			throw new ComhonException('query not initialized');
		}
		$inheritanceValuesLiteral = null;
		if (!empty($this->model->getSerialization()->getInheritanceValues())) {
			$values = $this->model->getSerialization()->getInheritanceValues();
			$inheritanceValuesLiteral = new SimpleDbLiteral(
				$this->selectQuery->getMainTable(),
				$this->model->getSerialization()->getInheritanceKey(),
				Literal::EQUAL,
				count($values) > 1 ? $values : $values[0]
			);
		}
		if (!is_null($this->filter)) {
			if ($this->optimizeLiterals) {
				$this->filter = ClauseOptimizer::optimizeLiterals($this->filter);
			}
			if (!is_null($inheritanceValuesLiteral)) {
				$clause = new Clause(Clause::CONJUNCTION);
				$clause->addElement($this->filter);
				$clause->addLiteral($inheritanceValuesLiteral);
				$this->selectQuery->where($clause);
			} else {
				$this->selectQuery->where($this->filter);
			}
		} elseif (!is_null($inheritanceValuesLiteral)) {
			$this->selectQuery->where($inheritanceValuesLiteral);
		}
		$this->selectQuery->setFocusOnMainTable();
		if (!$isCount) {
			$this->selectQuery->limit($this->limit)->offset($this->offset);
			$this->_addColumns();
			$this->_addOrderColumns();
		}
		if ($this->selectQuery->hasJoin()) {
			$this->_addGroupedColumns();
		}
		
		$this->selectQuery->verifyDuplicatedTables();
	}
	
	/**
	 * execute resquest and return resulting object
	 * 
	 * @return \Comhon\Object\ComhonArray
	 */
	public function execute() {
		$this->_finalize();
		$sqlTable = $this->model->getSqlTableSettings();
		$sqlTable->loadValue('database');
		$dbInstance = DatabaseHandler::getInstanceWithDataBaseObject($sqlTable->getValue('database'));
		$rows = $dbInstance->select($this->selectQuery);
		
		return $this->_buildObjectsWithRows($rows);
	}
	
	/**
	 * execute resquest and return objects count according filters.
	 * limit and order are ignored to get global objects count (usefull for pagination)
	 *
	 * @return integer
	 */
	public function count() {
		$this->_finalize(true);
		$sqlTable = $this->model->getSqlTableSettings();
		$sqlTable->loadValue('database');
		$dbInstance = DatabaseHandler::getInstanceWithDataBaseObject($sqlTable->getValue('database'));
		
		return $dbInstance->count($this->selectQuery);;
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
		if (!($rightProperty instanceof ForeignProperty) || !$rightProperty->getUniqueModel()->hasSqlTableSerialization()) {
			throw new IncompatibleLiteralSerializationException($rightProperty);
		}
		$database = $rightProperty->getUniqueModel()->getSqlTableSettings()->getValue('database');
		if (!($database instanceof UniqueObject)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		if ($database->getId() !== $databaseId) {
			throw new IncompatibleLiteralSerializationException($rightProperty);
		}
		$rightModel = $rightProperty->getUniqueModel();
		$rightTable = new TableNode($rightModel->getSqlTableSettings()->getValue('name'), $rightAliasTable, $selectAllColumns);
		
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
						$rightForeignProperty->getUniqueModel()->getUniqueIdProperty()->getSerializationName(),
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
					$rightModel->getUniqueIdProperty()->getSerializationName()
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
			$mainTable->resetSelectedColumns();
			foreach ($selectedColumns as $column) {
				$mainTable->addSelectedColumn($column);
			}
			if ($this->model->getSerialization()->getInheritanceKey()) {
				$mainTable->addSelectedColumn($this->model->getSerialization()->getInheritanceKey());
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
	 * @return \Comhon\Object\ComhonArray
	 */
	private function _buildObjectsWithRows($rows) {
		$modelArray = new ModelArray($this->model, false, $this->model->getName());
		return $modelArray->import($rows, SqlTable::getInstance()->getInterfacer());
	}
	
}