<?php

use comhon\api\ObjectService;
use comhon\model\Model;

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/comhon/source/');

require_once 'Comhon.php';

spl_autoload_register(function ($pClass) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php';
});

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"model" : "person",
	"id" : "1"
}';
/*
$time_start = microtime(true);

require_once 'comhon/model/Model.php';
require_once 'comhon/api/ObjectService.php';
require_once 'comhon/request/ObjectLoadRequest.php';
require_once 'comhon/request/SimpleLoadRequest.php';
require_once 'comhon/model/singleton/ModelManager.php';
require_once 'comhon/model/SimpleModel.php';
require_once 'comhon/model/ModelInteger.php';
require_once 'comhon/model/ModelFloat.php';
require_once 'comhon/model/ModelBoolean.php';
require_once 'comhon/model/ModelString.php';
require_once 'comhon/model/ModelDateTime.php';
require_once 'comhon/manifest/parser/ManifestParser.php';
require_once 'comhon/manifest/parser/json/JsonManifestParser.php';
require_once 'comhon/object/Object.php';
require_once 'comhon/object/extendable/Object.php';
require_once 'comhon/object/config/Config.php';
require_once 'comhon/interfacer/Interfacer.php';
require_once 'comhon/interfacer/StdObjectInterfacer.php';
require_once 'comhon/model/MainModel.php';
require_once 'comhon/manifest/parser/json/v_2_0/JsonManifestParser.php';
require_once 'comhon/model/LocalModel.php';
require_once 'comhon/model/property/Property.php';
require_once 'comhon/object/collection/ObjectCollection.php';
require_once 'comhon/object/collection/MainObjectCollection.php';
require_once 'comhon/visitor/Visitor.php';
require_once 'comhon/visitor/ObjectCollectionCreator.php';
require_once 'comhon/object/_final/Object.php';
require_once 'comhon/manifest/parser/SerializationManifestParser.php';
require_once 'comhon/manifest/parser/json/JsonSerializationManifestParser.php';
require_once 'comhon/manifest/parser/json/v_2_0/JsonSerializationManifestParser.php';
require_once 'comhon/model/ModelContainer.php';
require_once 'comhon/model/ModelForeign.php';
require_once 'comhon/model/property/ForeignProperty.php';
require_once 'comhon/model/ModelArray.php';
require_once 'comhon/model/property/AggregationProperty.php';
require_once 'comhon/serialization/SerializationUnit.php';
require_once 'comhon/serialization/SerializationFile.php';
require_once 'comhon/serialization/file/JsonFile.php';
require_once 'comhon/serialization/SqlTable.php';
require_once 'comhon/object/ObjectArray.php';
require_once 'comhon/database/LogicalJunction.php';
require_once 'comhon/database/Literal.php';
require_once 'comhon/database/DatabaseController.php';
require_once 'comhon/database/SelectQuery.php';
require_once 'comhon/database/TableNode.php';
require_once 'comhon/interfacer/AssocArrayInterfacer.php';
require_once 'comhon/object/ComhonDateTime.php';

$time_complex = microtime(true) - $time_start;
var_dump('load exec time '.$time_complex);
*/
$time_start = microtime(true);
$lResult = ObjectService::getObject(json_decode($Json));
$time_complex = microtime(true) - $time_start;
if (json_encode($lResult) !== '{"success":true,"result":{"id":"1","firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"__inheritance__":"man"}}') {
	var_dump(json_encode($lResult));
	throw new \Exception('bad result');
}

// average time : 0.045 s
var_dump('test exec time '.$time_complex);