<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"maxLength" : 1,
	"offset" : 0,
	"order" : [{"property":"id", "type":"DESC"}],
	"tree" : {
		"model"   : "person",
		"id"      : "p1",
		"children" : [
			{
				"property" : "children",
				"id"       : "p2",
				"children"  : [
					{
						"property" : "children",
						"id"       : "t_1"
					}
				]
			},
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
	"literalCollection" : [
		{
			"id"       : "l1",
			"node"     : "p1",
			"queue"    : {"property" : "children"},
			"having" : {
				"function" : "COUNT",
				"operator" : "=",
				"value"    : 3
			}
		},
		{
			"id"       : "l2",
			"node"     : "p1",
			"queue"    : {"property" : "children"},
			"having" : {
				"function" : "COUNT",
				"operator" : ">=",
				"value"    : 3
			}
		}
	],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"node"     : "t_1",
				"property" : "firstName",
				"operator" : "=",
				"value"    : ["louise", "mouha", null]
			},
			{
				"node"     : "homes",
				"property" : "endDate",
				"operator" : "<>",
				"value"    : ["louise", "mouha", null]
			},
			{
				"node"    : "house",
				"property" : "surface",
				"operator" : ">",
				"value"    : 200
			},
			{
				"node"      : "p1",
				"queue"     : {
					"property" : "homes",
					"child" : {
						"property" : "house"
					}
				},
				"having" : {
					"type" : "conjunction",
					"elements" : [
						{
							"function" : "AVG",
							"property" : "surface",
							"operator" : "=",
							"value"    : 170
						},
						{
							"function" : "COUNT",
							"operator" : "=",
							"value"    : 3
						}
					]
				}
			},
			{"id" : "l1"},
			{
				"type" : "conjunction",
				"elements" : [
					{
						"node"    : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"node"     : "p1",
						"queue"    : {"property" : "homes"},
						"having" : {
							"function" : "COUNT",
							"operator" : ">=",
							"value"    : 3
						}
					},
					{
						"node"    : "town",
						"property" : "name",
						"operator" : "=",
						"value"    : "Montpellier"
					},
					{"id" : "l1"},
					{"id" : "l2"}
				]
			}
		]
	}
}';

/******************************************** query result *************************************************/

// SELECT p1.* 
// FROM   person AS p1 
//        LEFT JOIN person AS p2 
//               ON ( p1.id = p2.mother_id 
//                     OR p1.id = p2.father_id ) 
//        LEFT JOIN home AS homes 
//               ON p1.id = homes.person_id 
//        LEFT JOIN place AS birthPlace 
//               ON p1.birth_place = birthPlace.id 
//        LEFT JOIN town AS town 
//               ON birthPlace.town = town.id 
//        LEFT JOIN house AS house 
//               ON homes.house_id = house.id 
//        LEFT JOIN person AS t_1 
//               ON ( p2.id = t_1.mother_id 
//                     OR p2.id = t_1.father_id ) 
//        LEFT JOIN (SELECT person.id 
//                   FROM   person 
//                          INNER JOIN person AS t_7 
//                                  ON ( person.id = t_7.mother_id 
//                                        OR person.id = t_7.father_id ) 
//                   GROUP  BY person.id 
//                   HAVING ( Count(*) = 3 )) AS t_8 
//               ON p1.id = t_8.id 
//        LEFT JOIN (SELECT person.id 
//                   FROM   person 
//                          INNER JOIN person AS t_9 
//                                  ON ( person.id = t_9.mother_id 
//                                        OR person.id = t_9.father_id ) 
//                   GROUP  BY person.id 
//                   HAVING ( Count(*) >= 3 )) AS t_10 
//               ON p1.id = t_10.id 
//        LEFT JOIN (SELECT t_11.person_id 
//                   FROM   home AS t_11 
//                   GROUP  BY t_11.person_id 
//                   HAVING ( Count(*) >= 3 )) AS t_12 
//               ON p1.id = t_12.person_id 
//        LEFT JOIN (SELECT t_13.person_id 
//                   FROM   home AS t_13 
//                          INNER JOIN house AS t_14 
//                                  ON t_13.house_id = t_14.id 
//                   GROUP  BY t_13.person_id 
//                   HAVING ( Avg(t_14.surface) = 170 
//                            AND Count(*) = 3 )) AS t_15 
//               ON p1.id = t_15.person_id 
// WHERE  ( ( t_1.first_name IN ( "louise", "mouha" ) 
//             OR t_1.first_name IS NULL ) 
//          AND ( homes.end_date NOT IN ( "louise", "mouha" ) 
//                AND homes.end_date IS NOT NULL ) 
//          AND house.surface > 200 
//          AND t_15.person_id IS NOT NULL 
//          AND t_8.id IS NOT NULL 
//          AND ( house.surface > 250 
//                AND t_12.person_id IS NOT NULL 
//                AND town.name = "montpellier" 
//                AND t_8.id IS NOT NULL 
//                AND t_10.id IS NOT NULL ) ) 
// GROUP  BY p1.id 
// ORDER  BY p1.id DESC 
// LIMIT  1 offset 0 


$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result),  '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"tree" : {
		"model"   : "person",
		"id"      : "p1",
		"children" : [
			{
				"property" : "homes",
				"id"       : "homes",
				"children"  : [
					{
						"property" : "house",
						"id"       : "houseux"
					}
				]
			}
		]
	},
	"filter" : {
		"node"     : "houseux",
		"property" : "surface",
		"operator" : "=",
		"value"    : 120
	}
}';

// SELECT p1.* FROM  person AS p1 left join home AS homes on p1.id = homes.person_id left join house AS houseux on homes.house_id = houseux.id_serial  WHERE (houseux.surface = 120) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}


$Json = '{
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"tree" : {
		"model"   : "person",
		"id"      : "p1"
	},
	"filter" : {
		"node"     : "p1",
		"property" : "firstName",
		"operator" : "=",
		"value"    : "Bernard"
	}
}';

// SELECT * FROM  person AS p1  WHERE (p1.first_name = Bernard) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('complex request test exec time '.($time_end - $time_start));