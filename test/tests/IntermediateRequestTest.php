<?php

use Test\Comhon\Service\ObjectService;

$time_start = microtime(true);

// SELECT t_1.*
// FROM   `person` AS t_1
//        LEFT JOIN place AS t_4
//               ON t_1.birth_place = t_4.id
//        LEFT JOIN home AS t_5
//               ON t_1.id = t_5.person_id
//        LEFT JOIN house AS t_2
//               ON t_5.house_id = t_2.id_serial
//        LEFT JOIN town AS t_3
//               ON t_4.town = t_3.id
//        LEFT JOIN (SELECT `person`.id
//                   FROM   `person`
//                          INNER JOIN `person` AS tq_0
//                                  ON ( `person`.id = tq_0.mother_id
//                                        OR `person`.id = tq_0.father_id )
//                   GROUP  BY `person`.id
//                   HAVING ( Count(*) > 1
//                            AND Count(*) <= 3 )) AS tq_1
//               ON t_1.id = tq_1.id
//        LEFT JOIN (SELECT tq_2.person_id
//                   FROM   home AS tq_2
//                          INNER JOIN house AS tq_3
//                                  ON tq_2.house_id = tq_3.id_serial
//                   GROUP  BY tq_2.person_id
//                   HAVING Avg(tq_3.surface) = 170) AS tq_4
//               ON t_1.id = tq_4.person_id
//       LEFT JOIN (SELECT tq_5.person_id
//                  FROM   home AS tq_5
//                  GROUP  BY tq_5.person_id
//                  HAVING ( Count(*) >= 3
//                            OR Count(*) > 2
//                            OR ( Count(*) >= 3
//                                 AND Count(*) > 2 ) )) AS tq_6
//              ON t_1.id = tq_6.person_id
// WHERE  ( ( t_1.first_name IN ( "paul", "bernard" )
//             OR t_1.first_name IS NULL )
//          AND t_2.surface > 200
//          AND tq_1.id IS NOT NULL
//          AND tq_4.person_id IS NOT NULL
//          AND ( t_2.surface > 250
//                AND tq_6.person_id IS NOT NULL
//                AND t_3.name = "montpellier" ) )
// GROUP  BY t_1.id  
$data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR . 'request.json';
$result = ObjectService::getObjects(json_decode(file_get_contents($data_ad)));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result1');
}

$Json = '{
	"simple_collection": [
		{
			"id": 1,
			"node"    : 1,
			"property" : "firstName",
			"operator" : "IN",
			"values"    : ["Paul", "Bernard", null],
			"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		}
	],
	"filter": 1,
	"root": 1,
	"models": [
		{
			"id": 1,
			"model": "Test\\\\Person"
		}
	],
	"__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';

// SELECT * FROM  person  WHERE ((person.first_name  IN  (Paul,Bernardo) or person.first_name is null)) GROUP BY person.id
$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 1');
}

$Json = '{
	"simple_collection": [
		{
			"id": 1,
			"node"    : 2,
			"property" : "surface",
			"operator" : "=",
			"value"    : 120,
			"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
		}
	],
	"filter": 1,
	"root": 1,
	"models": [
		{
			"id": 1,
			"model": "Test\\\\Person"
		},
		{
			"id": 2,
			"model": "Test\\\\House"
		}
	],
	"__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';

// SELECT person.* FROM  person left join home on person.id = home.person_id left join house on home.house_id = house.id_serial  WHERE (house.surface = 120) GROUP BY person.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 2');
}

$Json = '{
	"simple_collection": [
		{
			"id": 1,
			"node"    : 1,
			"property" : "surface",
			"operator" : "<>",
			"value"    : 120,
			"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
		}
	],
	"filter": 1,
	"root": 1,
	"models": [
		{
			"id": 1,
			"model": "Test\\\\Town"
		}
	],
	"__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';


// SELECT * FROM  public.town  WHERE (public.town.surface <> 120 or public.town.surface is null) GROUP BY public.town.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"Montpellier","surface":null,"cityHall":1}]}')) {
	throw new \Exception('bad result 3');
}

$Json = '{
	"simple_collection": [
		{
			"id": 1,
			"node"    : 1,
			"queue"     : ["childrenTestDb"],
			"having" : 1,
			"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Having"
		}
	],
	"having_collection": [
		{
			"id": 1,
			"function"    : "AVG",
			"property" : "parentTestDb",
			"operator" : "=",
			"value"    : 170,
			"__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Function"
		}
	],
	"filter": 1,
	"root": 1,
	"models": [
		{
			"id": 1,
			"model": "Test\\\\TestDb"
		}
	],
	"__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!compareJson(json_encode($result), '{"success":false,"error":{"message":"property \'parentTestDb\'not allowed, having-literal cannot reference multiple foreign property.","code":708}}')) {
	throw new \Exception('bad result 4');
}

$Json = '{
	"simple_collection": [
		{
			"id": 1,
			"node"    : 1,
			"property" : "parentTestDb",
			"operator" : "NOT IN",
			"values"    : ["[123, \"123\"]","[124, \"124\"]"],
			"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		}
	],
	"filter": 1,
	"root": 1,
	"models": [
		{
			"id": 1,
			"model": "Test\\\\ChildTestDb"
		}
	],
	"__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';


// SELECT * FROM  public.child_test  WHERE (
// ((public.child_test.parent_id_1 <> 123 or public.child_test.parent_id_1 is null) 
//   or (public.child_test.parent_id_2 <> 123 or public.child_test.parent_id_2 is null)) 
// and ((public.child_test.parent_id_1 <> 124 or public.child_test.parent_id_1 is null) 
//   or (public.child_test.parent_id_2 <> 124 or public.child_test.parent_id_2 is null))) GROUP BY public.child_test.id

$result = ObjectService::getObjects(json_decode($Json));

if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result 5');
}

$time_end = microtime(true);
var_dump('intermediate request test exec time '.($time_end - $time_start));