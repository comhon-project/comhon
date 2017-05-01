<?php
namespace comhon\serialization\file;

use comhon\serialization\SerializationFile;
use comhon\interfacer\XMLInterfacer;

class XmlFile extends SerializationFile {
	
	/**
	 *
	 * @return XMLInterfacer
	 */
	protected function _getInterfacer() {
		$lInterfacer = new XMLInterfacer();
		$lInterfacer->setSerialContext(true);
		$lInterfacer->setPrivateContext(true);
		$lInterfacer->setFlagValuesAsUpdated(false);
		return $lInterfacer;
	}
	
}