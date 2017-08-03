<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"model" : "person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
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

// SELECT person.* 
// FROM   person 
//        LEFT JOIN place 
//               ON person.birth_place = place.id 
//        LEFT JOIN town 
//               ON place.town = town.id 
//        LEFT JOIN home 
//               ON person.id = home.person_id 
//        LEFT JOIN house 
//               ON home.house_id = house.id 
//        LEFT JOIN (SELECT t_0.person_id 
//                   FROM   home AS t_0 
//                   GROUP  BY t_0.person_id 
//                   HAVING ( Count(*) >= 3 
//                             OR Count(*) > 2 
//                             OR ( Count(*) >= 3 
//                                  AND Count(*) > 2 ) )) AS t_1 
//               ON person.id = t_1.person_id 
//        LEFT JOIN (SELECT person.id 
//                   FROM   person 
//                          INNER JOIN person AS t_2 
//                                  ON ( person.id = t_2.mother_id 
//                                        OR person.id = t_2.father_id ) 
//                   GROUP  BY person.id 
//                   HAVING ( Count(*) > 1 
//                            AND Count(*) <= 3 )) AS t_3 
//               ON person.id = t_3.id 
//        LEFT JOIN (SELECT t_4.person_id 
//                   FROM   home AS t_4 
//                          INNER JOIN house AS t_5 
//                                  ON t_4.house_id = t_5.id 
//                   GROUP  BY t_4.person_id 
//                   HAVING ( Avg(t_5.surface) = 170 )) AS t_6 
//               ON person.id = t_6.person_id 
// WHERE  ( ( person.first_name IN ( "paul", "bernard" ) 
//             OR person.first_name IS NULL ) 
//          AND house.surface > 200 
//          AND t_3.id IS NOT NULL 
//          AND t_6.person_id IS NOT NULL 
//          AND ( house.surface > 250 
//                AND t_1.person_id IS NOT NULL 
//                AND town.NAME = "montpellier" ) ) 
// GROUP  BY person.id 

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}


$Json = '{
	"model" : "person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"model"    : "person",
		"property" : "firstName",
		"operator" : "=",
		"value"    : ["Paul", "Bernard", null]
	}
}';

// SELECT * FROM  person  WHERE ((person.first_name  IN  (Paul,Bernardo) or person.first_name is null)) GROUP BY person.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"model"    : "house",
		"property" : "surface",
		"operator" : "=",
		"value"    : 120
	}
}';

// SELECT person.* FROM  person left join home on person.id = home.person_id left join house on home.house_id = house.id_serial  WHERE (house.surface = 120) GROUP BY person.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"man"},{"id":6,"__inheritance__":"man"},{"id":11,"__inheritance__":"woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}]}')) {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('intermediate request test exec time '.($time_end - $time_start));