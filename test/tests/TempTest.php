<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Comhon\Exception\NotDefinedModelException;
use Comhon\Object\Collection\MainObjectCollection;

//Config::setLoadPath('./config/config-json-pgsql.json');

// $testDbModel = ModelManager::getInstance()->getInstanceModel('Test\Person\Man');

/*
ModelManager::getInstance()->getInstanceModel('Test\MainTestDb')->loadObject(2);
$mainTestDb = MainObjectCollection::getInstance()->getObject(2, 'Test\MainTestDb');
$mainTestDb->initValue('childrenTestDb', false);
*/

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


