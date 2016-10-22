<?php
namespace comhon\object;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelContainer;
use comhon\object\model\ForeignProperty;
use comhon\object\ObjectCollection;
use comhon\controller\Controller;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\CompositionLoader;

abstract class ObjectLoadRequest {

	protected $mModel;
	protected $mRequestChildren          = false;
	protected $mLoadForeignProperties    = false;
	
	public function __construct($pModelName) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
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
		$lCompositionLoader         = new CompositionLoader();

		if ($this->mRequestChildren && !$this->mLoadForeignProperties) {
			$lCompositionLoader->execute($pObject, array(CompositionLoader::LOAD_CHILDREN => $this->mLoadForeignProperties));
		}
		else if ($this->mLoadForeignProperties) {
			$lForeignObjectLoader->execute($pObject, array($this->mRequestChildren));
		}
		
		return $pObject;
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