<?php

use Test\Comhon\Service\ObjectService;
use Comhon\Object\Config\Config;

$loader = require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$loader->addPsr4('Test\\Comhon\\', __DIR__ . DIRECTORY_SEPARATOR . 'classes');

Config::setLoadPath(__DIR__.'/config/config-xml-mysql.json');

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
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "inheritance-": "Comhon\\\\Request\\\\Complex"
}';

$time_start = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_complex = microtime(true) - $time_start;
if (json_encode($result) !== '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]}') {
	throw new \Exception('bad result'.json_encode($result));
}

// average time : 0.017 s
var_dump('test exec time '.$time_complex);