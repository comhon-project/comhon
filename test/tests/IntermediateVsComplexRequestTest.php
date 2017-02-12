<?php

use comhon\api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"model" : "person",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"    : "person",
				"property" : "firstName",
				"operator" : "=",
				"value"    : ["Paul", "Bernard", null]
			},
			{
				"model"    : "house",
				"property" : "surface",
				"operator" : ">",
				"value"    : 200
			},
			{
				"model"     : "person",
				"queue"     : {"property" : "children"},
				"havingLogicalJunction" : {
					"type" : "conjunction",
					"literals" : [
						{
							"function" : "COUNT",
							"operator" : ">",
							"value"    : 1
						},
						{
							"function" : "COUNT",
							"operator" : "<=",
							"value"    : 3
						}
					]
				}
			},
			{
				"model"     : "person",
				"queue"     : {
					"property" : "homes", 
					"child" : {
						"property" : "house"
					}
				},
				"havingLiteral" : {
					"function" : "AVG",
					"property" : "surface",
					"operator" : "=",
					"value"    : 170
				}
			}
		],
		"logicalJunctions" : [
			{
				"type" : "conjunction",
				"literals" : [
					{
						"model"    : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"model"     : "person",
						"queue"     : {"property" : "homes"},
						"havingLogicalJunction" : {
							"type" : "disjunction",
							"literals" : [
								{
									"function" : "COUNT",
									"operator" : ">=",
									"value"    : 3
								},
								{
									"function" : "COUNT",
									"operator" : ">",
									"value"    : 2
								}
							],
							"logicalJunctions" : [
								{
									"type" : "conjunction",
									"literals" : [
										{
											"function" : "COUNT",
											"operator" : ">=",
											"value"    : 3
										},
										{
											"function" : "COUNT",
											"operator" : ">",
											"value"    : 2
										}
									]
								}
							]
						}
					},
					{
						"model"    : "town",
						"property" : "name",
						"operator" : "=",
						"value"    : "Montpellier"
					}
				],
				"logicalJunctions" : []
			}
		]
	}
}';

$time_start_intermediaire = microtime(true);
$lResult = ObjectService::getObjects(json_decode($Json));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (json_encode($lResult) !== '{"success":true,"result":[{"children":[{"id":"5","__inheritance__":"man"},{"id":"6","__inheritance__":"man"},{"id":"11","__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}]}') {
	var_dump(json_encode($lResult));
	throw new \Exception('bad result');
}

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"tree" : {
		"model"   : "person",
		"id"      : "person",
		"children" : [
			{
				"property" : "homes",
				"id"       : "homes",
				"children"  : [
					{
						"property" : "house",
						"id"       : "house"
					}
				]
			},
			{
				"property" : "birthPlace",
				"id"       : "birthPlace",
				"children"  : [
					{
						"property" : "town",
						"id"       : "town"
					}
				]
			}
		]
	},
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"node"     : "person",
				"property" : "firstName",
				"operator" : "=",
				"value"    : ["Paul", "Bernard", null]
			},
			{
				"node"     : "house",
				"property" : "surface",
				"operator" : ">",
				"value"    : 200
			},
			{
				"node"      : "person",
				"queue"     : {"property" : "children"},
				"havingLogicalJunction" : {
					"type" : "conjunction",
					"literals" : [
						{
							"function" : "COUNT",
							"operator" : ">",
							"value"    : 1
						},
						{
							"function" : "COUNT",
							"operator" : "<=",
							"value"    : 3
						}
					]
				}
			},
			{
				"node"      : "person",
				"queue"     : {
					"property" : "homes",
					"child" : {
						"property" : "house"
					}
				},
				"havingLiteral" : {
					"function" : "AVG",
					"property" : "surface",
					"operator" : "=",
					"value"    : 170
				}
			}
		],
		"logicalJunctions" : [
			{
				"type" : "conjunction",
				"literals" : [
					{
						"node"     : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"node"      : "person",
						"queue"     : {"property" : "homes"},
						"havingLogicalJunction" : {
							"type" : "disjunction",
							"literals" : [
								{
									"function" : "COUNT",
									"operator" : ">=",
									"value"    : 3
								},
								{
									"function" : "COUNT",
									"operator" : ">",
									"value"    : 2
								}
							],
							"logicalJunctions" : [
								{
									"type" : "conjunction",
									"literals" : [
										{
											"function" : "COUNT",
											"operator" : ">=",
											"value"    : 3
										},
										{
											"function" : "COUNT",
											"operator" : ">",
											"value"    : 2
										}
									]
								}
							]
						}
					},
					{
						"node"     : "town",
						"property" : "name",
						"operator" : "=",
						"value"    : "Montpellier"
					}
				],
				"logicalJunctions" : []
			}
		]
	}
}';

$time_start_complex = microtime(true);
$lResult = ObjectService::getObjects(json_decode($Json));
$time_complex = microtime(true) - $time_start_complex;
if (json_encode($lResult) !== '{"success":true,"result":[{"children":[{"id":"5","__inheritance__":"man"},{"id":"6","__inheritance__":"man"},{"id":"11","__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}]}') {
	var_dump(json_encode($lResult));
	throw new \Exception('bad result');
}

if ($time_complex > $time_intermediaire) {
	var_dump('Warning!!! intermediate request is faster than complex request');
}

/** *************************************************************************************************** **/

$Json = '{
	"model" : "person",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"literal" : {
		"model" : "home",
		"property" : "id",
		"operator" : "=",
		"value"    : 1
	}
}';

$time_start_intermediaire = microtime(true);
$lResult = ObjectService::getObjects(json_decode($Json));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (json_encode($lResult) !== '{"success":true,"result":[{"children":[{"id":"5","__inheritance__":"man"},{"id":"6","__inheritance__":"man"},{"id":"11","__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}]}') {
	var_dump(json_encode($lResult));
	throw new \Exception('bad result');
}

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"tree" : {
		"model"   : "person",
		"id"      : "p1",
		"children" : [
			{
				"property" : "homes",
				"id"       : "homeux"
			}
		]
	},
	"literal" : {
		"node"     : "homeux",
		"property" : "id",
		"operator" : "=",
		"value"    : 1
	}
}';

$time_start_complex = microtime(true);
$lResult = ObjectService::getObjects(json_decode($Json));
$time_complex = microtime(true) - $time_start_complex;
if (json_encode($lResult) !== '{"success":true,"result":[{"children":[{"id":"5","__inheritance__":"man"},{"id":"6","__inheritance__":"man"},{"id":"11","__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}]}') {
	var_dump(json_encode($lResult));
	throw new \Exception('bad result');
}

if ($time_complex > $time_intermediaire) {
	var_dump('Warning!!! intermediate request is faster than complex request');
}

$time_end = microtime(true);
var_dump('intermediate vs complex request test exec time '.($time_end - $time_start));