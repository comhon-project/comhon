<?php
namespace comhon\database;

class Conjunction extends LogicalJunction {
	
	public function __construct() {
		parent::__construct(LogicalJunction::CONJUNCTION);
	}
	
}