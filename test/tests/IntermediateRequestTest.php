<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"model" : "Test\\\\Person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "Test\\\\Person",
				"property" : "firstName",
				"operator" : "=",
				"value"    : ["Paul", "Bernard", null]
			},
			{
				"model"    : "Test\\\\House",
				"property" : "surface",
				"operator" : ">",
				"value"    : 200
			},
			{
				"model"     : "Test\\\\Person",
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
				"model"     : "Test\\\\Person",
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
						"model"    : "Test\\\\House",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"model"     : "Test\\\\Person",
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
						"model"    : "Test\\\\Town",
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
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":11,"__inheritance__":"Test\\\\Person\\\\Woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result1');
}


$Json = '{
	"model" : "Test\\\\Person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"model"    : "Test\\\\Person",
		"property" : "firstName",
		"operator" : "=",
		"value"    : ["Paul", "Bernard", null]
	}
}';

// SELECT * FROM  person  WHERE ((person.first_name  IN  (Paul,Bernardo) or person.first_name is null)) GROUP BY person.id
$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":11,"__inheritance__":"Test\\\\Person\\\\Woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "Test\\\\Person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"model"    : "Test\\\\House",
		"property" : "surface",
		"operator" : "=",
		"value"    : 120
	}
}';

// SELECT person.* FROM  person left join home on person.id = home.person_id left join house on home.house_id = house.id_serial  WHERE (house.surface = 120) GROUP BY person.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"children":[{"id":5,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":11,"__inheritance__":"Test\\\\Person\\\\Woman"}],"homes":[1,2,6],"bodies":[1,2],"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "Test\\\\Town",
	"filter" : {
		"model"    : "Test\\\\Town",
		"property" : "surface",
		"operator" : "<>",
		"value"    : 120
	}
}';


// SELECT * FROM  public.town  WHERE (public.town.surface <> 120 or public.town.surface is null) GROUP BY public.town.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"Montpellier","surface":null,"cityHall":1}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "Test\\\\TestDb",
	"filter" : {
		"model"     : "Test\\\\TestDb",
		"queue"     : {
			"property" : "childrenTestDb"
		},
		"having" : {
			"function" : "AVG",
			"property" : "parentTestDb",
			"operator" : "=",
			"value"    : 170
		}
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!compareJson(json_encode($result), '{"success":false,"error":{"message":"property \'parentTestDb\'not allowed, having-literal cannot reference multiple foreign property.","code":708}}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "Test\\\\ChildTestDb",
	"filter" : {
		"model"    : "Test\\\\ChildTestDb",
		"property" : "parentTestDb",
		"operator" : "<>",
		"value"    : ["[123, \"123\"]","[124, \"124\"]"]
	}
}';


// SELECT * FROM  public.child_test  WHERE (
// ((public.child_test.parent_id_1 <> 123 or public.child_test.parent_id_1 is null) 
//   or (public.child_test.parent_id_2 <> 123 or public.child_test.parent_id_2 is null)) 
// and ((public.child_test.parent_id_1 <> 124 or public.child_test.parent_id_1 is null) 
//   or (public.child_test.parent_id_2 <> 124 or public.child_test.parent_id_2 is null))) GROUP BY public.child_test.id

$result = ObjectService::getObjects(json_decode($Json));

if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('intermediate request test exec time '.($time_end - $time_start));