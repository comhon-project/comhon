<?php
namespace object;

use comhon\object\extendable\Object;
use comhon\object\ComhonDateTime;

class Person extends Object {
	
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