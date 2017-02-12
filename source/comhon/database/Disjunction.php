<?php
namespace comhon\database;

class Disjunction extends LogicalJunction {
	
	public function __construct() {
		parent::__construct(LogicalJunction::DISJUNCTION);
	}
	
}