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

use Comhon\Interfacer\Interfacer;

class LocalModel extends Model {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_getOrCreateObjectInstance()
	 */
	protected function _getOrCreateObjectInstance($id, Interfacer $interfacer, $localObjectCollection, $isFirstLevel, $isForeign = false) {
		$isloaded = !$isForeign && (!$isFirstLevel || $interfacer->hasToFlagObjectAsLoaded());
		
		if (is_null($id) || !$this->hasIdProperties()) {
			$object = $this->getObjectInstance($isloaded);
		}
		else {
			$object = $localObjectCollection->getObject($id, $this->modelName);
			if (is_null($object)) {
				$object = $this->_buildObjectFromId($id, $isloaded, $interfacer->hasToFlagValuesAsUpdated());
				$localObjectCollection->addObject($object);
			}
			elseif ($isloaded || ($isFirstLevel && $interfacer->getMergeType() !== Interfacer::MERGE)) {
				$object->setIsLoaded($isloaded);
			}
		}
		return $object;
	}
	
}