<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization\File;

use Comhon\Serialization\SerializationFile;
use Comhon\Interfacer\StdObjectInterfacer;

class JsonFile extends SerializationFile {
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationFile::_getInterfacer()
	 * 
	 * @return \Comhon\Interfacer\StdObjectInterfacer
	 */
	protected function _getInterfacer() {
		$interfacer = new StdObjectInterfacer();
		$interfacer->setSerialContext(true);
		$interfacer->setPrivateContext(true);
		$interfacer->setFlagValuesAsUpdated(false);
		return $interfacer;
	}
	
}