<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);

$Json = '{
	"model" : "mainTestDb",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "mainTestDb",
				"property" : "name",
				"operator" : "=",
				"value"    : ["azeaze", "Bernard", null]
			},
			{
				"model"     : "mainTestDb",
				"queue"     : {
					"property" : "childrenTestDb", 
					"child" : {
						"property" : "childrenTestDb"
					}
				},
				"having" : {
					"function" : "COUNT",
					"operator" : "=",
					"value"    : 2
				}
			}
		]
	}
}';

// SELECT main_test.* 
// FROM   main_test 
//        LEFT JOIN (SELECT t_16.main_test_id 
//                   FROM   test AS t_16 
//                          INNER JOIN child_test AS t_17 
//                                  ON ( t_16.id_1 = t_17.parent_id_1 
//                                       AND t_16.id_2 = t_17.parent_id_2 ) 
//                   GROUP  BY t_16.main_test_id 
//                   HAVING ( Count(*) = 2 )) AS t_18 
//               ON main_test.id = t_18.main_test_id 
// WHERE  ( ( main_test.NAME IN ( "azeaze", "bernard" ) 
//             OR main_test.NAME IS NULL ) 
//          AND t_18.main_test_id IS NOT NULL ) 
// GROUP  BY main_test.id 

$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"name":"azeaze","obj":null,"id":1,"childrenTestDb":["[1,\"23\"]","[1,\"50\"]","[1,\"101\"]","[1,\"1501774389\"]","[2,\"50\"]","[2,\"102\"]"]}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "mainTestDb",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "mainTestDb",
				"property" : "name",
				"operator" : "=",
				"value"    : ["azeaze", "Bernard", null]
			},
			{
				"model"     : "testDb",
				"queue"     : {
					"property" : "childrenTestDb"
				},
				"having" : {
					"function" : "COUNT",
					"operator" : "=",
					"value"    : 2
				}
			}
		]
	}
}';

// SELECT main_test.* 
// FROM   main_test 
//        LEFT JOIN test 
//               ON main_test.id = test.main_test_id 
//        LEFT JOIN (SELECT t_19.parent_id_1, 
//                          t_19.parent_id_2 
//                   FROM   child_test AS t_19 
//                   GROUP  BY t_19.parent_id_1, 
//                             t_19.parent_id_2 
//                   HAVING ( Count(*) = 2 )) AS t_20 
//               ON ( test.id_1 = t_20.parent_id_1 
//                    AND test.id_2 = t_20.parent_id_2 ) 
// WHERE  ( ( main_test.NAME IN ( "azeaze", "bernard" ) 
//             OR main_test.NAME IS NULL ) 
//          AND ( t_20.parent_id_1 IS NOT NULL 
//                AND t_20.parent_id_2 IS NOT NULL ) ) 
// GROUP  BY main_test.id 


$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), '{"success":true,"result":[{"name":"azeaze","obj":null,"id":1,"childrenTestDb":["[1,\"23\"]","[1,\"50\"]","[1,\"101\"]","[1,\"1501774389\"]","[2,\"50\"]","[2,\"102\"]"]}]}')) {
	throw new \Exception('bad result');
}

$Json = '{
	"model" : "childTestDb",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"order" : [{"property":"id", "type":"ASC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : ["nnnn", "bbbb", null]
			}
		]
	}
}';

// SELECT child_test.* 
// FROM   child_test 
//        LEFT JOIN test 
//               ON ( child_test.parent_id_1 = test.id_1 
//                    AND child_test.parent_id_2 = test.id_2 ) 
// WHERE  (( test.string IN ( "nnnn", "bbbb" ) 
//            OR test.string IS NULL )) 
// GROUP  BY child_test.id 

$result = ObjectService::getObjects(json_decode($Json), true);
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result');
}

$time_end = microtime(true);
var_dump('intermediate request test exec time '.($time_end - $time_start));