<?php

use comhon\object\singleton\ModelManager;
use comhon\object\object\Object;
use comhon\api\ObjectService;
use comhon\object\object\serialization\SqlTable;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;

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
			"havingLiteral" : {
				"function" : "COUNT",
				"operator" : "=",
				"value"    : 3
			}
		},
		{
			"id"       : "l2",
			"node"     : "p1",
			"queue"    : {"property" : "children"},
			"havingLiteral" : {
				"function" : "COUNT",
				"operator" : ">=",
				"value"    : 3
			}
		}
	],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
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
				"havingLogicalJunction" : {
					"type" : "conjunction",
					"literals" : [
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
			{"id" : "l1"}
		],
		"logicalJunctions" : [
			{
				"type" : "conjunction",
				"literals" : [
					{
						"node"    : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"node"     : "p1",
						"queue"    : {"property" : "homes"},
						"havingLiteral" : {
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
				],
				"logicalJunctions" : []
			}
		]
	}
}';

/******************************************** query result *************************************************/

// SELECT p1.* 
// FROM   person AS p1 
//        LEFT JOIN person AS p2 
//               ON p2.mother_id = p1.id 
//                   OR p2.father_id = p1.id 
//        LEFT JOIN home AS homes 
//               ON homes.person_id = p1.id 
//        LEFT JOIN place AS birthPlace 
//               ON birthPlace.id = p1.birth_place 
//        LEFT JOIN town AS town 
//               ON town.id = birthPlace.town 
//        LEFT JOIN house AS house 
//               ON house.id = homes.house_id 
//        LEFT JOIN person AS t_1 
//               ON t_1.mother_id = p2.id 
//                   OR t_1.father_id = p2.id 
//        LEFT JOIN (SELECT person.id 
//                   FROM   person 
//                          LEFT JOIN person AS person_0 
//                                 ON person_0.mother_id = person.id 
//                                     OR person_0.father_id = person.id 
//                   GROUP  BY person.id 
//                   HAVING ( Count(person.id) = 3 )) AS t_2 
//               ON t_2.id = p1.id 
//        LEFT JOIN (SELECT person.id 
//                   FROM   person 
//                          LEFT JOIN person AS person_3 
//                                 ON person_3.mother_id = person.id 
//                                     OR person_3.father_id = person.id 
//                   GROUP  BY person.id 
//                   HAVING ( Count(person.id) >= 3 )) AS t_4 
//               ON t_4.id = p1.id 
//        LEFT JOIN (SELECT home.person_id 
//                   FROM   home 
//                   GROUP  BY home.person_id 
//                   HAVING ( Count(home.person_id) >= 3 )) AS t_5 
//               ON t_5.person_id = p1.id 
//        LEFT JOIN (SELECT home.person_id 
//                   FROM   home 
//                          LEFT JOIN house 
//                                 ON house.id = home.house_id 
//                   GROUP  BY home.person_id 
//                   HAVING ( Avg(house.surface) = 170 
//                            AND Count(home.person_id) = 3 )) AS t_6 
//               ON t_6.person_id = p1.id 
// WHERE  ( ( t_1.first_name IN ( "louise", "mouha" ) 
//             OR t_1.first_name IS NULL ) 
//          AND ( homes.end_date NOT IN ( "louise", "mouha" ) 
//                AND homes.end_date IS NOT NULL ) 
//          AND house.surface > 200 
//          AND t_6.person_id IS NOT NULL 
//          AND t_2.id IS NOT NULL 
//          AND ( house.surface > 250 
//                AND t_5.person_id IS NOT NULL 
//                AND town.name = "montpellier" 
//                AND t_2.id IS NOT NULL 
//                AND t_4.id IS NOT NULL ) ) 
// GROUP  BY p1.id 
// ORDER  BY p1.id DESC 
// LIMIT  1 offset 0 


$lResult = ObjectService::getObjects(json_decode($Json));
if (json_encode($lResult) !== '{"success":true,"result":[{"children":[{"id":"5","__inheritance__":"man"},{"id":"6","__inheritance__":"man"},{"id":"11","__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}]}') {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('complex request test exec time '.($time_end - $time_start));