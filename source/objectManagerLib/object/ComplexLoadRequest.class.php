<?php
namespace objectManagerLib\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\LogicalJunctionOptimizer;
use objectManagerLib\database\Literal;
use objectManagerLib\database\ComplexLiteral;
use objectManagerLib\database\HavingLiteral;
use objectManagerLib\database\SelectQuery;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\controller\ForeignObjectReplacer;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\controller\CompositionLoader;
use objectManagerLib\dataStructure\Tree;
use objectManagerLib\exception\PropertyException;
use \Exception;

class ComplexLoadRequest extends ObjectLoadRequest {
	
	const CREATE = "create";
	const UPDATE = "update";
	const DELETE = "delete";
	const DELETE_CASCADE = "deleteCascade";
	const CREATE_OR_UPDATE = "createOrUpdate";

	private $mModelTree;
	private $mSelectQuery;
	private $mLogicalJunction;
	private $mAliasCount = 1;
	private $mLoadLength;
	private $mOrder = array();
	private $mOffset;
	private $mOptimizeLiterals = false;
	private $mKey;
	
	public function __construct($pModelName) {
		parent::__construct($pModelName);
		if (!$this->mModel->hasSqlTableUnit()) {
			trigger_error("error : resquested model must have a database serialization");
			throw new Exception("error : resquested model must have a database serialization");
		}
		$this->mLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
	}
	
	public function setMaxLength($pInteger) {
		$this->mLoadLength = $pInteger;
		return $this;
	}
	
	public function addOrder($pPropertyName, $pType = SelectQuery::ASC) {
		$this->mOrder[] = array($pPropertyName, $pType);
		return $this;
	}
	
	public function setOffset($pInteger) {
		$this->mOffset = $pInteger;
		return $this;
	}
	
	public function opitimizeLiterals($pBoolean) {
		$this->mOptimizeLiterals = $pBoolean;
		return $this;
	}
	
	public function setLogicalJunction($pLogicalJunction) {
		$this->mLogicalJunction = $pLogicalJunction;
		return $this;
	}
	
	public function setLiteral($pLiteral) {
		$this->mLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		$this->mLogicalJunction->addLiteral($pLiteral);
		return $this;
	}
	
	public function setKey($pKey) {
		if (!is_null($pKey) && (!$this->mModel->hasProperty($pKey) || ! ($this->mModel->getProperty($pKey)->getModel() instanceof SimpleModel))) {
			trigger_error("key '".$pKey."' unauthorized");
			throw new Exception("key '".$pKey."' unauthorized");
		}
		$this->mKey = $pKey;
	}
	
	/**
	 * 
	 * @param stdClass $pPhpObject
	 */
	public static function buildObjectLoadRequest($pPhpObject) {
		if (isset($pPhpObject->model)) {
			$lObjectLoadRequest = new ComplexLoadRequest($pPhpObject->model);
		} else if (isset($pPhpObject->tree) && isset($pPhpObject->tree->model)) {
			$lObjectLoadRequest = new ComplexLoadRequest($pPhpObject->tree->model);
			$lObjectLoadRequest->importModelTree($pPhpObject->tree);
		} else {
			throw new Exception("request doesn't have model");
		}
		if (isset($pPhpObject->logicalJunction) && isset($pPhpObject->literal)) {
			throw new Exception('can\'t have logicalJunction and literal properties in same time');
		}
		if (isset($pPhpObject->logicalJunction)) {
			$lObjectLoadRequest->importLogicalJunction($pPhpObject->logicalJunction);
		}
		else if (isset($pPhpObject->literal)) {
			$lObjectLoadRequest->importLiteral($pPhpObject->literal);
		}
		if (isset($pPhpObject->maxLength)) {
			$lObjectLoadRequest->setMaxLength($pPhpObject->maxLength);
		}
		if (isset($pPhpObject->offset)) {
			$lObjectLoadRequest->setOffset($pPhpObject->offset);
		}
		if (isset($pPhpObject->order)) {
			if (!is_array($pPhpObject->order)) {
				throw new Exception("order parameter must be an array");
			}
			foreach ($pPhpObject->order as $lOrder) {
				if (!isset($lOrder->property)) {
					throw new Exception("an order element doesn't have property");
				}
				$lObjectLoadRequest->addOrder($lOrder->property, isset($lOrder->type) ? $lOrder->type : SelectQuery::ASC);
			}
		}
		if (isset($pPhpObject->requestChildren)) {
			$lObjectLoadRequest->requestChildren($pPhpObject->requestChildren);
		}
		if (isset($pPhpObject->loadForeignProperties)) {
			$lObjectLoadRequest->loadForeignProperties($pPhpObject->loadForeignProperties);
		}
		return $lObjectLoadRequest;
	}
	
	/**
	 * @param stdClass $pModelTree
	 */
	public function importModelTree($pModelTree) {
		if (!isset($pModelTree->model)) {
			throw new Exception("model tree doesn't have model");
		}
		if ($pModelTree->model != $this->mModel->getModelName()) {
			throw new Exception("root model in model tree is not the same as model specified in constructor");
		}
		$lSqlTable = $this->mModel->getSqlTableUnit();
		$lAlias = isset($pModelTree->id) ? $pModelTree->id : null;
		$this->mSelectQuery = new SelectQuery($lSqlTable->getValue("name"), $lAlias);
		
		$this->mModelTree = new Tree(array('left_model' => $this->mModel, 'right_model' => $this->mModel, "right_table" => $lSqlTable->getValue("name"), "right_table_alias" => $lAlias));
		$this->mModelTree->saveCurrentNode(is_null($lAlias) ? $lSqlTable->getValue("name") : $lAlias);
		
		$lStack = array(array($this->mModel, $lSqlTable, $lAlias, $pModelTree));
		while (count($lStack) > 0) {
			$lLastElement    = array_pop($lStack);
			$lLeftModel      = $lLastElement[0];
			$lLeftTable      = $lLastElement[1];
			$lLeftAliasTable = $lLastElement[2];
			$lLeftTableName  = is_null($lLeftAliasTable) ? $lLeftTable->getValue("name") : $lLeftAliasTable;
			$lChildrenNodes  = isset($lLastElement[3]->children) ? $lLastElement[3]->children : array();
			$this->mModelTree->goToSavedNodeAt($lLeftTableName);
			
			foreach ($lChildrenNodes as $lChildNode) {
				if (!$lLeftModel->hasProperty($lChildNode->property)) {
					throw new Exception("property '{$lChildNode->property}' in model '{$lLeftModel->getModelName()}' doesn't exist");
				}
				$lProperty = $lLeftModel->getProperty($lChildNode->property);
				if (!($lProperty instanceof ForeignProperty) || !$lProperty->hasSqlTableUnit()) {
					throw new Exception("property '{$lChildNode->property}' in model '{$lLeftModel->getModelName()}' hasn't sql serialization");
				}
				$lLeftJoin                      = $this->_prepareLeftJoin($lLeftTable, $lLeftModel, $lProperty);
				$lLeftJoin["left_table"]        = $lLeftTableName;
				$lLeftJoin["right_table_alias"] = isset($lChildNode->id) ? $lChildNode->id : null;
				
				$this->mModelTree->pushChild($lLeftJoin);
				$this->mModelTree->saveLastChild($lLeftJoin["right_table_alias"]);
				$lStack[] = array($lProperty->getUniqueModel(), $lProperty->getSqlTableUnit(), $lLeftJoin["right_table_alias"], $lChildNode);
			}
		}
		$this->mModelTree->goToRoot();
		return $this;
	}
	
	public function importLogicalJunction($pPhpObjectLogicalJunction) {
		if (is_null($this->mModelTree)) {
			$lMainTableName = $this->mModel->getSqlTableUnit()->getValue("name");
			$this->mSelectQuery = new SelectQuery($lMainTableName);
			$this->mModelTree   = new Tree(array('left_model' => $this->mModel, 'right_model' => $this->mModel, "right_table" => $lMainTableName, "right_table_alias" => null));
			$this->mModelTree->saveCurrentNode($lMainTableName);

			$lModels = array();
			$this->_getModelLiterals($pPhpObjectLogicalJunction, $lMainTableName, $lModels);
			$this->_buildModelTree($lModels);
		}
		$this->setLogicalJunction(LogicalJunction::phpObjectToLogicalJunction($pPhpObjectLogicalJunction, $this->mModelTree));
		
		$this->mModelTree->goToRoot();
		$this->mModelTree->initDepthFirstSearch();
		if ($this->mModelTree->next()) { // skip first node
			while ($lLeftJoin = $this->mModelTree->next()) {
				$lAlias = array_key_exists('right_table_alias', $lLeftJoin) ? $lLeftJoin['right_table_alias'] : null;
				$this->mSelectQuery->addTable($lLeftJoin["right_table"], $lAlias, SelectQuery::LEFT_JOIN, $lLeftJoin["right_column"], $lLeftJoin["left_column"], $lLeftJoin["left_table"]);
			}
		}
	}
	
	public function importLiteral($pPhpObjectLiteral) {
		if (is_null($this->mModelTree)) {
			$this->_buildModelTree(array($pPhpObjectLiteral->model => null));
		}
		$this->setLiteral(Literal::phpObjectToLiteral($pPhpObjectLiteral, $this->mModelTree));
	}
	
	/**
	 * execute request
	 * @param unknown $pFakeValue parent function has parameter so we add on to have same number of parameter
	 * @return array
	 */
	public function execute($pFakeValue = null) {
		$lReturn = array();
		if (is_null($this->mSelectQuery)) {
			throw new \Exception("query not initialized");
		}
		if ($this->mOptimizeLiterals) {
			$this->mLogicalJunction = LogicalJunctionOptimizer::optimizeLiterals($this->mLogicalJunction);
		}
		$lSqlTable = $this->mModel->getSqlTableUnit();
		$lSqlTable->loadValue("database");
		
		$this->mSelectQuery->setWhereLogicalJunction($this->mLogicalJunction);
		$this->mSelectQuery->setLimit($this->mLoadLength)->setOffset($this->mOffset);
		$this->mSelectQuery->setFirstTableCurrentTable();
		$this->_addColumns();
		$this->_addGroupedColumns();
		
		foreach ($this->mOrder as $lOrder) {
			if (!$this->mModel->hasProperty($lOrder[0])) {
				throw new Exception("property doesn't exists");
			}
			$this->mSelectQuery->addOrderColumn($this->mModel->getProperty($lOrder[0])->getSerializationName(), $lOrder[1]);
		}
		$lDbInstance = DatabaseController::getInstanceWithDataBaseObject($lSqlTable->getValue("database"));
		$lRows = $lDbInstance->executeQuery($this->mSelectQuery);
		return $this->_buildObjectsWithRows($lRows);
	}
	
	private function _getModelLiterals($pPhpObjectLogicalJunction, $pMainTableName, &$pModels) {
		if (isset($pPhpObjectLogicalJunction->literals)) {
			foreach ($pPhpObjectLogicalJunction->literals as $lLiteral) {
				if (!array_key_exists($lLiteral->model, $pModels)) {
					$pModels[$lLiteral->model] = array('alias' => array(), 'literalsWithoutAlias' => array());
				}
				if ($lLiteral->model == $this->mModel->getModelName()) {
					$lLiteral->node = $pMainTableName;
					unset($lLiteral->model);
				}
				else if (isset($lLiteral->function)) {
					$lAliasSuffix = "t".$this->mAliasCount;
					$pModels[$lLiteral->model]['alias'][] = $lAliasSuffix;
					$lLiteral->node = $this->_getAlias($lLiteral->model, $lAliasSuffix);
					$this->mAliasCount++;
				}
				else {
					$pModels[$lLiteral->model]['literalsWithoutAlias'][] = $lLiteral;
				}
				unset($lLiteral->model);
			}
		}
		if (isset($pPhpObjectLogicalJunction->logicalJunctions)) {
			foreach ($pPhpObjectLogicalJunction->logicalJunctions as $lLogicalJunction) {
				$this->_getModelLiterals($lLogicalJunction, $pMainTableName, $pModels);
			}
		}
	}
	
	/**
	 * add table to query $mSelectQuery
	 * @param array $pModels [modelName => alias]
	 * @throws Exception
	 */
	private function _buildModelTree($pModels) {
		if ((count($pModels) == 0) || ((count($pModels) == 1) && array_key_exists($this->mModel->getModelName(), $pModels))) {
			return;
		}
		$lTemporaryLeftJoins = array();
		$lStackVisitedModels = array();
		$lArrayVisitedModels = array();
		$lStack              = array();
		$lMainModelTableName = $this->mModel->getSqlTableUnit()->getValue("name");
		
		// stack initialisation with $pModel
		$this->_extendsStacks($this->mModel, $this->mModel->getSqlTableUnit(), $pModels, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
		// Depth-first search to build all left joins
		while ((count($lStack) > 0)) {
			if ($lStack[count($lStack) - 1]["current"] != -1) {
				array_pop($lTemporaryLeftJoins);
				$lModelName = array_pop($lStackVisitedModels);
				$lArrayVisitedModels[$lModelName] -= 1;
			}
			$lStack[count($lStack) - 1]["current"]++;
			if ($lStack[count($lStack) - 1]["current"] < count($lStack[count($lStack) - 1]["properties"])) {
				$lStackIndex     = count($lStack) - 1;
				$lRightProperty  = $lStack[$lStackIndex]["properties"][$lStack[$lStackIndex]["current"]];
				$lRightModel     = $lRightProperty->getUniqueModel();
				$lRightModelName = $lRightModel->getModelName();
	
				if (array_key_exists($lRightModelName, $lArrayVisitedModels) && ($lArrayVisitedModels[$lRightModelName] > 0)) {
					$lStackVisitedModels[] = $lRightModelName;
					$lArrayVisitedModels[$lRightModelName] += 1;
					$lTemporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $lRightModel is in literals ($pModels)
				$lTemporaryLeftJoins[] = $this->_prepareLeftJoin($lStack[$lStackIndex]["leftTable"], $lStack[$lStackIndex]["leftModel"], $lRightProperty);
				if (array_key_exists($lRightModelName, $pModels)) {
					$this->_addJoins($lTemporaryLeftJoins, $pModels[$lRightModelName]['alias'], $pModels[$lRightModelName]['literalsWithoutAlias']);
					if (count($pModels[$lRightModelName]['literalsWithoutAlias']) > 0) {
						$lTemporaryLeftJoins = array();
					}
				}
				// add serializable properties to stack
				$this->_extendsStacks($lRightModel, $lRightProperty->getSqlTableUnit(), $pModels, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (count($lStack[count($lStack) - 1]["properties"]) == 0) {
					array_pop($lStack);
				}
			}
			else {
				array_pop($lStack);
				array_pop($lTemporaryLeftJoins);
			}
		}
	}
	
	private function _addJoins($pLeftJoins, $pAliasArray, $pLiteralsWithoutAlias) {
		$lTableByOriginalTable = array($pLeftJoins[0]["left_table"] => $pLeftJoins[0]["left_table"]);
		
		foreach ($pAliasArray as $lAlias) {
			foreach ($pLeftJoins as $lLeftJoin) {
				$lLeftJoin["left_table"]        = $lTableByOriginalTable[$lLeftJoin["left_table"]];
				$lLeftJoin["right_table_alias"] = $this->_getAlias($lLeftJoin["right_table"], $lAlias);
				$lTableByOriginalTable[$lLeftJoin["right_table"]] = $lLeftJoin["right_table_alias"];
				
				$this->mModelTree->goToSavedNodeAt($lLeftJoin["left_table"]);
				$this->mModelTree->pushChild($lLeftJoin);
				$this->mModelTree->saveLastChild($lLeftJoin["right_table_alias"]);
			}
		}
		
		if (count($pLiteralsWithoutAlias) > 0) {
			foreach ($pLeftJoins as $lLeftJoin) {
				$this->mModelTree->goToSavedNodeAt($lLeftJoin["left_table"]);
				$this->mModelTree->pushChild($lLeftJoin);
				$this->mModelTree->saveLastChild($lLeftJoin["right_table"]);
			}
			$lLiteralNode = $pLeftJoins[count($pLeftJoins) - 1]["right_table"];
			foreach ($pLiteralsWithoutAlias as $pLiteral) {
				$pLiteral->node = $lLiteralNode;
			}
		}
	}
	
	private function _getAlias($pModelName, $pSuffix) {
		return is_null($pSuffix) ? $pModelName : $pModelName."_".$pSuffix;
	}
	
	private function _extendsStacks($pModel, $pSqlTable, $pLiteralsByModelName, &$pStack, &$pStackVisitedModels, &$pArrayVisitedModels) {
		if (array_key_exists($pModel->getModelName(), $pArrayVisitedModels) && array_key_exists($pModel->getModelName(), $pLiteralsByModelName)) {
			throw new Exception("Cannot resolve literal. Literal with model '".$pModel->getModelName()."' can be applied on several properties");
		}
		$pStack[] = array(
				"leftTable"  => $pSqlTable,
				"leftModel"  => $pModel,
				"properties" => $pModel->getSerializableProperties("sqlTable"),
				"current"    => -1
		);
		$lModelName = $pModel->getModelName();
		$pStackVisitedModels[] = $lModelName;
		$pArrayVisitedModels[$lModelName] = array_key_exists($lModelName, $pArrayVisitedModels) ? $pArrayVisitedModels[$lModelName] + 1 : 1;
	}
	
	private function _prepareLeftJoin($pLeftTable, $pLeftModel, $pRightProperty) {
		$lRightTable = $pRightProperty->getSqlTableUnit();
		$lReturn = array(
			"left_model"   => $pLeftModel,
			"right_model"  => $pRightProperty->getUniqueModel(),
			"left_table"   => $pLeftTable->getValue("name"),
			"right_table"  => $lRightTable->getValue("name")
		);
		$lColumn = $pRightProperty->getSerializationName();
		if ($lRightTable->isComposition($pLeftModel, $lColumn)) {
			$lReturn["left_column"] = $pLeftModel->getProperty($pLeftModel->getFirstId())->getSerializationName();
			$lReturn["right_column"] = $lRightTable->getCompositionColumns($pLeftModel, $lColumn);
		}else {
			$lRightModel = $pRightProperty->getUniqueModel();
			$lReturn["left_column"] = $lColumn;
			$lReturn["right_column"] = $lRightModel->getProperty($lRightModel->getFirstId())->getSerializationName();
		}
		return $lReturn;
	}
	
	/**
	 * add select columns to $mSelectQuery
	 */
	private function _addColumns() {
		foreach ($this->mModel->getProperties() as $lProperty) {
			if (!($lProperty instanceof ForeignProperty) || !$lProperty->hasSqlTableUnitComposition($this->mModel)) {
				$this->mSelectQuery->addSelectColumn($lProperty->getSerializationName());
			}
		}
	}
	
	private function _addGroupedColumns() {
		foreach ($this->mModel->getIds() as $lPropertyName) {
			$this->mSelectQuery->addGroupColumn($this->mModel->getProperty($lPropertyName)->getSerializationName());
		}
	}
	
	private function _buildObjectsWithRows($pRows) {
		$lObjects = array();
		foreach ($pRows as $lRow) {
			$lObjects[] = $this->mModel->fromSqlDataBase($lRow);
		}
		return $this->_updateObjects($lObjects);
	}
	
	/*
	 * $pOperationList is an array of sdtClass objects.
	 * exemple :
	 *    [
	 *    	0 : {operation : "update", id : "12", metada : {...}},
	 *    	1 : {operation : "create", metada : {...}},
	 *    	2 : {operation : "delete", id : "12"}
	 *    ]
	 */
	public function manageObjectList($pOperationList, $pSaveData = true) {
		$lReturn = array();
		
		foreach ($pOperationList as $lStdClassObject) {
			try {
				if (!isset($lStdClassObject->operation)) {
					throw new Exception("operation missing");
				}
				switch ($lStdClassObject->operation) {
					case self::CREATE:
						$lResult = $this->_createOrUpdateObject($lStdClassObject, true);
					break;
					case self::UPDATE:
						$lResult = $this->_createOrUpdateObject($lStdClassObject, false);
					break;
					case self::CREATE_OR_UPDATE:
						if (isset($lStdClassObject->id)) {// if there is an id that means the object has already been created so we do an update
							$lResult = $this->_createOrUpdateObject($lStdClassObject, false);
						}else {// else we do an insert
							$lResult = $this->_createOrUpdateObject($lStdClassObject, true);
						}
					break;
					case self::DELETE_CASCADE:
						 //TODO if it's possible remove children and then go in DELETE
					case self::DELETE:
						$lResult = $this->_deleteObject($lStdClassObject->id);
					break;
					default:
						throw new Exception("operation not recognize : ".$lStdClassObject->operation);
					break;
				}
			}catch (Exception $e) {
				trigger_error(get_class($this));
				$lResult = $this->_setErrorObject($e, $lStdClassObject->id);
			}
			$lReturn[] = $lResult;
		}
		/*if ($pSaveData) {
			$lExchangePointController = ExchangePointController::getInstance();
			$lExchangePointController->sendAllData();
		}*/
		return $lReturn;
	}
	
	/*
	 * update the dataBase
	 * just put files in $pBasePath will not save them. They must be sent to ADFData so $pBasePath should be a path to an exchange point
	 * 
	 * $pCreate indicate if object must be created or updated
	 * 
	 */
	protected function _createOrUpdateObject($pStdClassObject, $pCreate) {
		try {
			$this->_checkSentModel($pStdClassObject, $pCreate);
			$lObject = $this->_getOrCreateObject($pStdClassObject, $pCreate);
			$lResult = $lObject->toObjectPrimayKey();
			$lResult->success = true;
		}catch (Exception $e) {
			$lResult = $this->_setErrorObject($e, $pStdClassObject->id);
		}
		return $lResult;
	}
	
	protected function _checkSentModel($pStdClassObject, $pCreate) {
		if ((!$pCreate && !isset($pStdClassObject->id)) || ($pCreate && (!isset($pStdClassObject->metadata)))) {
			throw new Exception("Bad object");
		}
	}
	
	protected function _getOrCreateObject($pStdClassObject, $pCreate) {
		if ($pCreate) {
			$lObject = clone $this->mObjectReference;
			$lId = (isset($pStdClassObject->id)) ? $pStdClassObject->id : null;
			$this->_createObject($lObject, $pStdClassObject->metadata, $lId);
		}else {
			$lObject = $this->_getObjectfromSqlDataBaseWithId($pStdClassObject->id);
			if (is_null($lObject)) {
				throw new Exception("object does not exist");
			}
			if (isset($pStdClassObject->metadata)) {
				$this->_updateObject($lObject, $pStdClassObject->metadata);
			}
		}
		return $lObject;
	}
	
	protected function _setErrorObject($pException, $pId = null) {
		$lResult = new stdClass();
		$lResult->success = false;
		$lResult->error = new stdClass();
		$lResult->error->code = $pException->getCode();
		$lResult->error->message = $pException->getMessage();
		if (!is_null($pId)) {
			$lResult->id = $pId;
		}
		
		return $lResult;
	}
	
}