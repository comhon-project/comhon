<?php

use Comhon\Api\ObjectService;
use Comhon\Model\Model;

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/comhon/src/');

require_once 'Comhon.php';

spl_autoload_register(function ($class) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
});

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"model" : "person",
	"id" : 1
}';

$time_start = microtime(true);
$result = ObjectService::getObject(json_decode($Json));
$time_complex = microtime(true) - $time_start;
if (json_encode($result) !== '{"success":true,"result":{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"man"}}') {
	throw new \Exception('bad result');
}

// average time : 0.045 s
var_dump('test exec time '.$time_complex);