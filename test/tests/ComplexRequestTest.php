<?php

use Test\Comhon\Service\ObjectService;

$time_start = microtime(true);

//  SELECT t_1.*
// FROM   `person` AS t_1
//        LEFT JOIN `person` AS t_2
//               ON ( t_1.id = t_2.mother_id
//                     OR t_1.id = t_2.father_id )
//        LEFT JOIN home AS t_4
//               ON t_1.id = t_4.person_id
//        LEFT JOIN place AS t_6
//               ON t_1.birth_place = t_6.id
//        LEFT JOIN town AS t_7
//               ON t_6.town = t_7.id
//        LEFT JOIN house AS t_5
//               ON t_4.house_id = t_5.id_serial
//        LEFT JOIN `person` AS t_3
//               ON ( t_2.id = t_3.mother_id
//                     OR t_2.id = t_3.father_id )
//        LEFT JOIN (SELECT tq_8.person_id
//                   FROM   home AS tq_8
//                          INNER JOIN house AS tq_9
//                                  ON tq_8.house_id = tq_9.id_serial
//                   GROUP  BY tq_8.person_id
//                   HAVING ( Avg(tq_9.surface) = 170
//                            AND Count(*) = 3 )) AS tq_10
//               ON t_1.id = tq_10.person_id
//        LEFT JOIN (SELECT `person`.id
//                   FROM   `person`
//                          INNER JOIN `person` AS tq_11
//                                  ON ( `person`.id = tq_11.mother_id
//                                        OR `person`.id = tq_11.father_id )
//                   GROUP  BY `person`.id
//                   HAVING Count(*) = 3) AS tq_12
//               ON t_1.id = tq_12.id
//        LEFT JOIN (SELECT tq_13.person_id
//                   FROM   home AS tq_13
//                   GROUP  BY tq_13.person_id
//                   HAVING Count(*) >= 3) AS tq_14
//               ON t_1.id = tq_14.person_id
//        LEFT JOIN (SELECT `person`.id
//                   FROM   `person`
//                          INNER JOIN `person` AS tq_15
//                                  ON ( `person`.id = tq_15.mother_id
//                                        OR `person`.id = tq_15.father_id )
//                   GROUP  BY `person`.id
//                   HAVING Count(*) >= 3) AS tq_16
//               ON t_1.id = tq_16.id
// WHERE  ( ( t_3.first_name IN ( "louise", "mouha" )
//             OR t_3.first_name IS NULL )
//          AND ( t_4.end_date NOT IN ( "louise", "mouha" )
//                AND t_4.end_date IS NOT NULL )
//          AND t_5.surface > 200
//          AND tq_10.person_id IS NOT NULL
//          AND tq_12.id IS NOT NULL
//          AND ( t_5.surface > 250
//                AND tq_14.person_id IS NOT NULL
//                AND t_7.name = "montpellier"
//                AND tq_12.id IS NOT NULL
//                AND tq_16.id IS NOT NULL ) )
// GROUP  BY t_1.id  

$data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Complex' . DIRECTORY_SEPARATOR . 'request.json';
$result = ObjectService::getObjects(json_decode(file_get_contents($data_ad)));
if (!compareJson(json_encode($result),  '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 1');
}

$Json = '{
	"tree" : {
		"model"   : "Test\\\\Person",
		"id"      : 1,
		"nodes" : [
			{
				"property" : "homes",
				"id"       : 2,
				"nodes"  : [
					{
						"property" : "house",
						"id"       : 3
					}
				]
			}
		]
	},
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 3,
			"property" : "surface",
			"operator" : "=",
			"value"    : 120,
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

// SELECT p1.* FROM  person AS p1 left join home AS homes on p1.id = homes.person_id left join house AS houseux on homes.house_id = houseux.id_serial  WHERE (houseux.surface = 120) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 2');
}

$Json = '{
	"tree" : {
		"model"   : "Test\\\\Person",
		"id"      : 1
	},
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "firstName",
			"operator" : "=",
			"value"    : "Bernard",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

// SELECT * FROM  person AS p1  WHERE (p1.first_name = Bernard) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 3');
}

$Json = '{
	"tree" : {
		"model"   : "Test\\\\ChildTestDb",
		"id"      : 1
	},
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "parentTestDb",
			"operator" : "=",
			"value"    : "[1,\"1501774389\"]",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

// SELECT * FROM  public.child_test AS p1  WHERE (p1.parent_id_1 = 1 and p1.parent_id_2 = 1501774389) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result 4');
}

$Json = '{
	"tree" : {
		"model"   : "Test\\\\ChildTestDb",
		"id"      : 1
	},
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "parentTestDb",
			"operator" : "IN",
			"values"    : ["[1,\"1501774389\"]","[11,\"1501774389\"]"],
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

// SELECT * FROM  public.child_test AS p1  WHERE ((p1.parent_id_1 = 1 and p1.parent_id_2 = 1501774389) or (p1.parent_id_1 = 1 and p1.parent_id_2 = 1501774389)) GROUP BY p1.id

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result 5');
}

$time_end = microtime(true);
var_dump('complex request test exec time '.($time_end - $time_start));