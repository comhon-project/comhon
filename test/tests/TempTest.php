<?php


use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Model\ModelArray;
use Comhon\Model\Property\Property;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\ModelForeign;
use Comhon\Serialization\File\ManifestFile;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Utils\Model;

//Config::setLoadPath('./config/config-json-pgsql.json');
/*
$unit = new ManifestFile('xml');
$model = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest');
foreach (Model::getValidatedProjectModelNames(null, false) as $modelName) {
	if (in_array($modelName, ['Test\Load\Malformed', 'Test\Manifest_V_2', 'Test\Manifest_V_2\Inherited_V_2'])) {
		continue;
	}
	$obj = $model->loadObject($modelName);
	echo $obj;
	// $unit->saveObject($obj);
}
die();
*/

/*
$interfacer = new AssocArrayInterfacer();
$model = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest');
$object = $model->import(
	$interfacer->read('/home/jean-philippe/ReposGit/comhon/src/Comhon/Manifest/Collection/Manifest/Config/manifest.json'),
	$interfacer
		);
$interfacer = new AssocArrayInterfacer('yaml');
$interfacer->write(
	$object->export($interfacer),
	'/home/jean-philippe/ReposGit/comhon/src/Comhon/Manifest/Collection/Manifest/Config/manifest.yaml',
		true
);
die();
*/

