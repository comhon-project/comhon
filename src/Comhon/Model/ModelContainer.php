<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

abstract class ModelContainer extends ModelComplex {

	/**
	 * @var AbstractModel model of object array elements
	 */
	protected $model;
	
	/**
	 * @param AbstractModel $model contained model
	 */
	public function __construct(AbstractModel $model) {
		$this->model = $model;
	}
	
	/**
	 * get contained model
	 * 
	 * @return \Comhon\Model\Model|\Comhon\Model\ModelArray|\Comhon\Model\SimpleModel
	 */
	public function getModel() {
		$this->model->load();
		return $this->model;
	}
	
	/**
	 * get unique contained model
	 * 
	 * a model container may contain another container so this function permit to 
	 * get the final unique model that is not a container
	 * 
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getUniqueModel() {
		$uniqueModel = $this->model;
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
	}
	
	/**
	 * verify unique model inside model container is a simple model
	 *
	 * @return bool
	 */
	public function isUniqueModelSimple() {
		return $this->model instanceof ModelContainer
			? $this->model->isUniqueModelSimple()
			: $this->model instanceof SimpleModel;
	}
	
	/**
	 * verify if specified model is equal to this model container
	 * 
	 * verify if model are same instance or if they have same contained model
	 * 
	 * @param AbstractModel $model
	 * @return boolean
	 */
	public function isEqual(AbstractModel $model) {
		return parent::isEqual($model) || ((get_class($this) == get_class($model)) && $this->model->isEqual($model->model));
	}
	
}