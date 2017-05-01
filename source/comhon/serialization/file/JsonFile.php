<?php
namespace comhon\serialization\file;

use comhon\serialization\SerializationFile;
use comhon\interfacer\StdObjectInterfacer;

class JsonFile extends SerializationFile {
	
	/**
	 *
	 * @return StdObjectInterfacer
	 */
	protected function _getInterfacer() {
		$lInterfacer = new StdObjectInterfacer();
		$lInterfacer->setSerialContext(true);
		$lInterfacer->setPrivateContext(true);
		$lInterfacer->setFlagValuesAsUpdated(false);
		return $lInterfacer;
	}
	
}