<?php
namespace objectManagerLib\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\LogicalJunctionOptimizer;
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

class ComplexLoadRequest extends LoadObjectRequest {
	
	const CREATE = "create";
	const UPDATE = "update";
	const DELETE = "delete";
	const DELETE_CASCADE = "deleteCascade";
	const CREATE_OR_UPDATE = "createOrUpdate";

	private $mLoadLength;
	private $mOptimizeLiterals = false;
	private $mKey;
	
	public function loadLength($pBoolean) {
		$this->mLoadLength = $pBoolean;
		return $this;
	}
	
	public function opitimizeLiterals($pBoolean) {
		$this->mOptimizeLiterals = $pBoolean;
		return $this;
	}
	
	public function setKey($pKey) {
		if (!is_null($pKey) && (!$this->mModel->hasProperty($pKey) || ! ($this->mModel->getProperty($pKey)->getModel() instanceof SimpleModel))) {
			trigger_error("key '".$pKey."' unauthorized");
			throw new \Exception("key '".$pKey."' unauthorized");
		}
		$this->mKey = $pKey;
	}
	
	/**
	 * 
	 * @param LogicalJunction $pLogicalJunction literal(s) for query (can be an object Literal)
	 * @return array
	 */
	public function execute($pLogicalJunction = null) {
		$lReturn = array();
		if (is_null($lSqlTable = $this->mModel->getSqlTableUnit())) {
			trigger_error("error : resquested model must have a database serialization");
			throw new \Exception("error : resquested model must have a database serialization");
		}
		if (is_null($pLogicalJunction)) {
			$lLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		}else if ($pLogicalJunction instanceof LogicalJunction) {
			$lLogicalJunction = $pLogicalJunction;
		}else {
			$lLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
			$lLogicalJunction->addLiteral($pLogicalJunction);
		}
		if ($this->mOptimizeLiterals) {
			$lLogicalJunction = LogicalJunctionOptimizer::optimizeLiterals($lLogicalJunction);
		}
		$lSqlTable->loadValue("database");
		$lSelectQuery = new SelectQuery($lSqlTable->getValue("name"));
		$lSelectQuery->setWhereLogicalJunction($lLogicalJunction);
		$this->_addColumns($lSelectQuery);
		$this->_addGroupedColumns($lSelectQuery);
		$this->_addTablesForQuery($lSelectQuery);
		$lDbInstance = DatabaseController::getInstanceWithDataBaseObject($lSqlTable->getValue("database"));
		$lRows = $lDbInstance->executeQuery($lSelectQuery);
		
		return $this->_buildObjectsWithRows($lRows);
	}
	
	/**
	 * add table to query $pSelectQuery (add left joins and set table in literals)
	 * @param SelectQuery $pSelectQuery
	 * @param pModel $pModel
	 * @param LogicalJunction $pLogicalJunction
	 * @throws \Exception
	 */
	private function _addTablesForQuery($pSelectQuery) {
		$lTemporaryLeftJoins = array();
		$lStackVisitedModels = array();
		$lArrayVisitedModels = array();
		$lModelTable = $pSelectQuery->getCurrentTableName();
		$lStack = array();
	
		$lLiteralsByModelName = array();
		$lWhere  = $pSelectQuery->getWhereLogicalJunction() instanceof LogicalJunction ? $pSelectQuery->getWhereLogicalJunction()->getFlattenedLiterals() : array();
		$lHaving = $pSelectQuery->getHavingLogicalJunction() instanceof LogicalJunction ? $pSelectQuery->getHavingLogicalJunction()->getFlattenedLiterals() : array();
		$lLiterals = array_merge($lWhere, $lHaving);
		foreach ($lLiterals as $lLiteral) {
			if (is_null($lLiteral->getModelName())) {
				throw new \Exception("all literals must have modelName to know related serialization");
			}
			if (!array_key_exists($lLiteral->getModelName(), $lLiteralsByModelName)) {
				$lLiteralsByModelName[$lLiteral->getModelName()] = array();
			}
			$lLiteralsByModelName[$lLiteral->getModelName()][] = $lLiteral;
			if ($lLiteral->getModelName() == $this->mModel->getModelName()) {
				$lLiteral->setTable($lModelTable);
			}
			if ($lLiteral instanceof ComplexLiteral) {
				if (count($this->mModel->getIds()) != 1) {
					throw new \Exception("error : query with complex literal must have one and only one column id");
				}
				$lSubSelectQuery = $lLiteral->getValue();
				$lWhere = $lSubSelectQuery->getWhereLogicalJunction();
				$lHaving = $lSubSelectQuery->getHavingLogicalJunction();
				$lColumnId = $this->mModel->getProperty($this->mModel->getFirstId())->getSerializationName();
				$lSubSelectQuery->init($lModelTable)->addSelectColumn($lColumnId);
				$lSubSelectQuery->setWhereLogicalJunction($lWhere)->setHavingLogicalJunction($lHaving)->addGroupColumn($lColumnId);
				$this->_addTablesForQuery($lSubSelectQuery, $this->mModel);
			} else if (($lLiteral instanceof HavingLiteral) && $lLiteral->hasModelNameForJoin()) {
				if (!array_key_exists($lLiteral->getModelNameForJoin(), $lLiteralsByModelName)) {
					$lLiteralsByModelName[$lLiteral->getModelNameForJoin()] = array();
				}
			}
		}
	
		// stack initialisation with $pModel
		$this->_extendsStacks($this->mModel, $this->mModel->getSqlTableUnit(), $lLiteralsByModelName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
		// Depth-first search to build all left joins
		while ((count($lStack) > 0)) {
			if ($lStack[count($lStack) - 1]["current"] != -1) {
				array_pop($lTemporaryLeftJoins);
				$lModelName = array_pop($lStackVisitedModels);
				$lArrayVisitedModels[$lModelName] -= 1;
			}
			$lStack[count($lStack) - 1]["current"]++;
			if ($lStack[count($lStack) - 1]["current"] < count($lStack[count($lStack) - 1]["properties"])) {
				$lStackIndex    = count($lStack) - 1;
				$lRightProperty = $lStack[$lStackIndex]["properties"][$lStack[$lStackIndex]["current"]];
				$lRightModel    = $lRightProperty->getModel()->getModel();
				$lRightModel    = ($lRightModel instanceof ModelContainer) ? $lRightModel->getModel() : $lRightModel;
	
				if (array_key_exists($lRightModel->getModelName(), $lArrayVisitedModels) && ($lArrayVisitedModels[$lRightModel->getModelName()] > 0)) {
					$lStackVisitedModels[] = $lRightModel->getModelName();
					$lArrayVisitedModels[$lRightModel->getModelName()] += 1;
					$lTemporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $lRightModel is in literals ($lLiteralsByModelName)
				$lTemporaryLeftJoins[] = $this->_prepareLeftJoin($lStack[$lStackIndex]["leftTable"], $lStack[$lStackIndex]["leftModel"], $lStack[$lStackIndex]["leftId"], $lRightModel, $lRightProperty);
				if (array_key_exists($lRightModel->getModelName(), $lLiteralsByModelName)) {
					foreach ($lTemporaryLeftJoins as $lLeftJoin) {
						$pSelectQuery->addTable($lLeftJoin["right_table"], null, SelectQuery::LEFT_JOIN, $lLeftJoin["right_column"], $lLeftJoin["left_column"], $lLeftJoin["left_table"]);
					}
					$lLiteralTable = $lTemporaryLeftJoins[count($lTemporaryLeftJoins) - 1]["right_table"];
					foreach ($lLiteralsByModelName[$lRightModel->getModelName()] as $lLiteral) {
						$lLiteral->setTable($lLiteralTable);
					}
					$lTemporaryLeftJoins = array();
				}
				// add serializable properties to stack
				$this->_extendsStacks($lRightModel, $lRightProperty->getSqlTableUnit(), $lLiteralsByModelName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
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
	
	private function _extendsStacks($pModel, $pSqlTable, $pLiteralsByModelName, &$pStack, &$pStackVisitedModels, &$pArrayVisitedModels) {
		if (array_key_exists($pModel->getModelName(), $pArrayVisitedModels) && array_key_exists($pModel->getModelName(), $pLiteralsByModelName)) {
			throw new \Exception("Cannot resolve literal. Literal with model '".$pModel->getModelName()."' can be applied on several properties");
		}
		$lIds = $pModel->getIds();
		$pStack[] = array(
				"leftId"     => (is_array($lIds) && (count($lIds) > 0)) ? $pModel->getProperty($lIds[0])->getSerializationName() : null,
				"leftTable"  => $pSqlTable,
				"leftModel"  => $pModel,
				"properties" => $pModel->getSerializableProperties("sqlTable"),
				"current"    => -1
		);
		$lModelName = $pModel->getModelName();
		$pStackVisitedModels[] = $lModelName;
		$pArrayVisitedModels[$lModelName] = array_key_exists($lModelName, $pArrayVisitedModels) ? $pArrayVisitedModels[$lModelName] + 1 : 1;
	}
	
	private function _prepareLeftJoin($pLeftTable, $pLeftModel, $pLeftId, $pRightModel, $pRightProperty) {
		$lRightTable = $pRightProperty->getSqlTableUnit();
		$lReturn = array(
				"left_table"   => $pLeftTable->getValue("name"),
				"right_table"  => $lRightTable->getValue("name")
		);
		$lColumn = $pRightProperty->getSerializationName();
		if ($lRightTable->isComposition($pLeftModel, $lColumn)) {
			$lReturn["left_column"] = $pLeftId;
			$lReturn["right_column"] = $lRightTable->getCompositionColumns($pLeftModel, $lColumn);
		}else {
			$lIds = $pRightModel->getIds();
			$lReturn["left_column"] = $lColumn;
			$lReturn["right_column"] = $pRightModel->getProperty($lIds[0])->getSerializationName();
		}
		$lReturn["right_model"] = $pRightModel;
		return $lReturn;
	}
	
	/**
	 * @param SelectQuery $pSelectQuery
	 */
	private function _addColumns($pSelectQuery) {
		foreach ($this->mModel->getProperties() as $lProperty) {
			if (!($lProperty instanceof ForeignProperty) || !$lProperty->hasSqlTableUnitComposition($this->mModel)) {
				$pSelectQuery->addSelectColumn($lProperty->getSerializationName());
			}
		}
	}
	
	private function _addGroupedColumns($pSelectQuery) {
		foreach ($this->mModel->getIds() as $lPropertyName) {
			$pSelectQuery->addGroupColumn($this->mModel->getProperty($lPropertyName)->getSerializationName());
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