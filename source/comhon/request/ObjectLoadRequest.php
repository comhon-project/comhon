<?php
namespace comhon\request;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\model\Model;
use comhon\model\SimpleModel;
use comhon\model\ModelContainer;
use comhon\model\property\ForeignProperty;
use comhon\object\collection\ObjectCollection;
use comhon\controller\Controller;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\AggregationLoader;

abstract class ObjectLoadRequest {

	protected $mModel;
	protected $mRequestChildren          = false;
	protected $mLoadForeignProperties    = false;
	
	public function __construct($pModelName) {
		$this->mModel = ModelManager::getInstance()->getInstanceModel($pModelName);
	}
	
	public abstract function execute($pValue);
		
	public function requestChildren($pBoolean) {
		$this->mRequestChildren = $pBoolean;
		return $this;
	}
	
	public function loadForeignProperties($pBoolean) {
		$this->mLoadForeignProperties = $pBoolean;
		return $this;
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	protected function _updateObjects($pObject) {
		$lForeignObjectLoader       = new ForeignObjectLoader();
		$lAggregationLoader         = new AggregationLoader();

		if ($this->mRequestChildren && !$this->mLoadForeignProperties) {
			$lAggregationLoader->execute($pObject, [AggregationLoader::LOAD_CHILDREN => $this->mLoadForeignProperties]);
		}
		else if ($this->mLoadForeignProperties) {
			$lForeignObjectLoader->execute($pObject, [$this->mRequestChildren]);
		}
		
		return $pObject;
	}
	
}