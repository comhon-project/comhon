<?php
namespace Test\Comhon\Object;

use Comhon\Object\ExtendableObject;
use Comhon\Object\ComhonDateTime;

class Person extends ExtendableObject {
	
	protected function _getModelName() {
		return 'Test\Person';
	}
	
	public function setFirstName($firstName) {
		$this->setValue('firstName', $firstName);
	}
	
	public function setLastName($firstName) {
		$this->setValue('lastName', $firstName);
	}
	
	public function setBirthDate(ComhonDateTime $birthDate) {
		$this->setValue('birthDate', $birthDate, true, false);
	}
}