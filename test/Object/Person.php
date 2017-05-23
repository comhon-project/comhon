<?php
namespace Object;

use Comhon\Object\ExtendableObject;
use Comhon\Object\ComhonDateTime;

class Person extends ExtendableObject {
	
	protected function _getModelName() {
		return 'person';
	}
	
	public function setFirstName($pFirstName) {
		$this->setValue('firstName', $pFirstName);
	}
	
	public function setLastName($pFirstName) {
		$this->setValue('lastName', $pFirstName);
	}
	
	public function setBirthDate(ComhonDateTime $pBirthDate) {
		$this->setValue('birthDate', $pBirthDate, true, false);
	}
}