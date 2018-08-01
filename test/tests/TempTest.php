<?php

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\Config\Config;
use Comhon\Exception\NotDefinedModelException;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

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


