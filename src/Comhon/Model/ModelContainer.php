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
	 * verify if contained model is complex or not
	 *
	 * model is complex if model is not instance of SimpleModel
	 *
	 * @return boolean
	 */
	public function isComplex() {
		return true;
	}
	
	/**
	 * get contained model
	 * 
	 * @return \Comhon\Model\Model
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
	 * get unique contained model
	 *
	 * a model container may contain another container so this function permit to
	 * get the final unique model that is not a container
	 *
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	protected function _getUniqueModel() {
		$uniqueModel = $this->model;
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->model;
		}
		return $uniqueModel;
	}
	
}