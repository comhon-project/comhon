<?php
namespace objectManagerLib\database;

class Disjunction extends LogicalJunction {
	
	public function __construct() {
		parent::__construc(LogicalJunction::OR);
	}
	
}