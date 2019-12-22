<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Restriction;

use Comhon\Model\AbstractModel;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelString;

class ModelName extends Restriction {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::satisfy()
	 */
	public function satisfy($value) {
		$satisfied = true;
		try {
			ModelManager::getInstance()->getInstanceModel($value);
		} catch (\Exception $e) {
			$satisfied = false;
		}
		return $satisfied;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isEqual()
	 */
	public function isEqual(Restriction $restriction) {
		return $this === $restriction || (($restriction instanceof ModelName));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::isAllowedModel()
	 */
	public function isAllowedModel(AbstractModel $model) {
		return $model instanceof ModelString;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toMessage()
	 */
	public function toMessage($value) {
		return $this->satisfy($value) 
			? "model '$value' exists" 
			: "model '$value' doesn't exist" ;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Restriction\Restriction::toString()
	 */
	public function toString() {
		return 'Model name';
	}
	
}