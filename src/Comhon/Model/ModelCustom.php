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

class ModelCustom extends Model {

	/**
	 * 
	 * @param string $modelName
	 * @param \Comhon\Model\Property\Property[] $properties
	 */
	public function __construct($modelName, $properties) {
		$this->modelName = $modelName;
		$this->isLoaded = true;
		$this->_setProperties($properties);
	}
	
}