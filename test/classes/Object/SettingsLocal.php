<?php
namespace Test\Comhon\Object;

use Comhon\Object\ExtendableObject;

class SettingsLocal extends ExtendableObject {
	
	protected function _getModelName() {
		return 'Test\Settings\Local';
	}
	
}