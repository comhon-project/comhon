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
	protected $mPropertiesFilter;
	protected $mPrivate;
	
	public function __construct($pModelName, $pPrivate = false) {
		$this->mModel = ModelManager::getInstance()->getInstanceModel($pModelName);
		$this->mPrivate = $pPrivate;
	}
	
	public abstract function execute();
	
	private $mId;
	
	/**
	 *
	 * @param string[] $pPropertiesFilter
	 */
	public function setPropertiesFilter($pPropertiesFilter) {
		if (empty($pPropertiesFilter)) {
			return;	
		}
		$this->mPropertiesFilter = [];
		// ids have to be in selected columns so if they are not defined in filter, we add them
		foreach ($this->mModel->getIdProperties() as $lProperty) {
			$this->mPropertiesFilter[] = $lProperty->getName();
		}
		// add defined columns
		foreach ($pPropertiesFilter as $pPropertyName) {
			$lProperty = $this->mModel->getProperty($pPropertyName, true);
			if ($lProperty->isAggregation()) {
				throw new \Exception("aggregation property '$pPropertyName' can't be a filter property");
			} else if (!$this->mPrivate && $lProperty->isPrivate()) {
				throw new \Exception("private property '$pPropertyName' can't be a filter property for public request");
			}
			else {
				$this->mPropertiesFilter[] = $pPropertyName;
			}
		}
		// remove possible duplicated columns
		$this->mPropertiesFilter = array_unique($this->mPropertiesFilter);
	}
		
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