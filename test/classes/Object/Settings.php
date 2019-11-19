<?php
namespace Test\Comhon\Object;

use Comhon\Object\ExtendableObject;

class Settings extends ExtendableObject {
	
	protected function _getModelName() {
		return 'Test\Settings';
	}
	
}