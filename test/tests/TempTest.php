<?php


use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Model\ModelArray;
use Comhon\Model\Property\Property;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\Property\RestrictedProperty;
use Comhon\Model\ModelForeign;
use phpDocumentor\Reflection\Types\Self_;

//Config::setLoadPath('./config/config-json-pgsql.json');

/*foreach (scandir(__DIR__) as $resource) {
	if ($resource !== '.' && $resource !== '..') {
		$content = file_get_contents(__DIR__ . '/' . $resource);
		$newContent = preg_replace_callback(
			"/Test\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\[A-Z]/",  // 
			function ($matches) {
				if ($matches[0]) {
					global $i;
					$i++;
					$match = $matches[0];
					$newValue = 'Test\\\\\\\\\\\\\\\\' . strtoupper(substr($match, -1));
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
$std = json_decode('{
    "limit": 1,
    "offset": 0,
    "order": [
        {
            "type": "ASC",
            "property": "<>"
        }
    ],
    "simpleCollection": [
        {
            "id": 0,
            "node": "house",
            "property": "surfaceInt",
            "operator": ">",
            "value": 200,
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
        },
        {
            "id": 1,
            "node": "house",
            "property": "surface",
            "operator": ">",
            "value": 200.05,
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Float"
        },
        {
            "id": 2,
            "node": "house",
            "property": "name",
            "operator": "<>",
            "value": "lalala",
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
        },
        {
            "id": 3,
            "node": "plop",
            "property": "is",
            "operator": "<>",
            "value": true,
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Boolean"
        },
        {
            "id": 4,
            "node": "house",
            "property": "surfaceInt",
            "operator": "IN",
            "values": [
                200,
                300
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\Numeric\\\\Integer"
        },
        {
            "id": 5,
            "node": "house",
            "property": "surface",
            "operator": "IN",
            "values": [
                200.05555,
                300
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\Numeric\\\\Float"
        },
        {
            "id": 6,
            "node": "house",
            "property": "name",
            "operator": "NOT IN",
            "values": [
                "azeaze",
                "hehehe"
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
        },
        {
            "id": 7,
            "elements": [
                0,
                1,
                2
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Clause\\\\Disjunction"
        },
        {
            "id": 8,
            "elements": [
                7,
                3,
                4,
                5,
                6,
                9
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Clause\\\\Conjunction"
        },
        {
            "id": 9,
            "node": "house",
            "queue": [
                "rooms"
            ],
            "having": 12,
            "__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Having"
        }
    ],
    "havingCollection": [
        {
            "id": 10,
            "operator": ">",
            "property": "surface",
            "function": "MIN",
            "value": 12.5,
            "__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Function"
        },
        {
            "id": 11,
            "operator": ">",
            "value": 2,
            "__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Count"
        },
        {
            "id": 12,
            "elements": [
                10,
                11
            ],
            "__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Clause\\\\Disjunction"
        }
    ],
    "filter": 8
}');

$stdInterfacer = new StdObjectInterfacer();
$xmlInterfacer = new XMLInterfacer();

$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request');
$request = $model->import($std, $stdInterfacer);
echo $request;
echo $request->getValue('filter');
echo $request->getValue('simpleCollection')->getValue(7)->getValue('elements')->getValue(0);
echo $request->getValue('simpleCollection')->getValue(7)->getValue('elements')->getValue(1);
echo $request->getValue('simpleCollection')->getValue(8);
var_dump($xmlInterfacer->toString($xmlInterfacer->export($request), true));
var_dump($stdInterfacer->toString($stdInterfacer->export($request), true));


die();*/
