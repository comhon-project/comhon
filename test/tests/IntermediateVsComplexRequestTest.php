<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"model" : "person",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"filter" : {
		"type" : "conjunction",
		"elements" : [
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
				"having" : {
					"type" : "conjunction",
					"elements" : [
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
				"having" : {
					"function" : "AVG",
					"property" : "surface",
					"operator" : "=",
					"value"    : 170
				}
			},
			{
				"type" : "conjunction",
				"elements" : [
					{
						"model"    : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"model"     : "person",
						"queue"     : {"property" : "homes"},
						"having" : {
							"type" : "disjunction",
							"elements" : [
								{
									"function" : "COUNT",
									"operator" : ">=",
									"value"    : 3
								},
								{
									"function" : "COUNT",
									"operator" : ">",
									"value"    : 2
								},
								{
									"type" : "conjunction",
									"elements" : [
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
				]
			}
		]
	}
}';

$time_start_intermediaire = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
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
	"filter" : {
		"type" : "conjunction",
		"elements" : [
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
				"having" : {
					"type" : "conjunction",
					"elements" : [
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
				"having" : {
					"function" : "AVG",
					"property" : "surface",
					"operator" : "=",
					"value"    : 170
				}
			},
			{
				"type" : "conjunction",
				"elements" : [
					{
						"node"     : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"node"      : "person",
						"queue"     : {"property" : "homes"},
						"having" : {
							"type" : "disjunction",
							"elements" : [
								{
									"function" : "COUNT",
									"operator" : ">=",
									"value"    : 3
								},
								{
									"function" : "COUNT",
									"operator" : ">",
									"value"    : 2
								},
								{
									"type" : "conjunction",
									"elements" : [
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
				]
			}
		]
	}
}';

$time_start_complex = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_complex = microtime(true) - $time_start_complex;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
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
	"filter" : {
		"model" : "home",
		"property" : "id",
		"operator" : "=",
		"value"    : 1
	}
}';

$time_start_intermediaire = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
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
	"filter" : {
		"node"     : "homeux",
		"property" : "id",
		"operator" : "=",
		"value"    : 1
	}
}';

$time_start_complex = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_complex = microtime(true) - $time_start_complex;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}

if ($time_complex > $time_intermediaire) {
	var_dump('Warning!!! intermediate request is faster than complex request');
}

$time_end = microtime(true);
var_dump('intermediate vs complex request test exec time '.($time_end - $time_start));