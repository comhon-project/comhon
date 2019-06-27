<?php


use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;

//Config::setLoadPath('./config/config-json-pgsql.json');

/*foreach (scandir(__DIR__) as $resource) {
	if ($resource !== '.' && $resource !== '..') {
		$content = file_get_contents(__DIR__ . '/' . $resource);
		$newContent = preg_replace_callback(
			"/Test\\\\\\\\\\\\\\\\\\\\\\\\[A-Z]/",  // 
			function ($matches) {
				if ($matches[0]) {
					global $i;
					$i++;
					$match = $matches[0];
					$newValue = 'Test\\\\\\\\' . strtoupper(substr($match, -1));
					var_dump($match." - $i > ".$newValue);
					return $newValue;
				}
			},
			$content
			);
		//file_put_contents(__DIR__ . '/' . $resource, $newContent);
	}
}*/
/*
var_dump('1-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
$interfacer = new StdObjectInterfacer();
$personModel = ModelManager::getInstance()->getInstanceModel('Test\Person');
var_dump('2-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));

$person = $personModel->getObjectInstance();
$std = new stdClass();
$std->firstName = 'hehe';
$std->father = 1;
var_dump('21-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
$person->fill($std, $interfacer);
var_dump('22-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));

var_dump($interfacer->toString($person->export($interfacer), true));
var_dump('3-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));

$person = $personModel->getObjectInstance();
$std = new stdClass();
$std->firstName = 'hehe2';
$std->father = 1;
$std->birthPlace = null;
$person->fill($std, $interfacer);

var_dump($interfacer->toString($person->export($interfacer), true));

var_dump('4-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));
ModelManager::getInstance()->getInstanceModel('Test\Place');
var_dump('5-'.ModelManager::getInstance()->hasInstanceModelLoaded('Test\Place'));

die();*/

