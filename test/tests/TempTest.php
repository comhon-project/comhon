<?php


use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Model\ModelArray;
use Comhon\Model\Property\Property;
use Comhon\Model\Restriction\Enum;
use Comhon\Model\ModelForeign;
use phpDocumentor\Reflection\Types\Self_;
use Comhon\Serialization\File\ManifestFile;
use Comhon\Interfacer\AssocArrayInterfacer;

//Config::setLoadPath('./config/config-json-pgsql.json');
/*
function getDirContents($dir, &$results = array()) {
	$files = scandir($dir);
	
	foreach($files as $value) {
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			$results[] = $path;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
			$results[] = $path;
		}
	}
	
	return $results;
}

$folders = [
		dirname(__DIR__) . '/manifests/manifest/' => 'Test',
		dirname(dirname(__DIR__)) . '/src/Comhon/Manifest/Collection/Manifest/' => 'Comhon'
];
$unit = new ManifestFile('json');
$model = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest');
foreach ($folders as $folder => $prefix) {
	$files = [];
	$files = getDirContents($folder, $files);
	
	foreach ($files as $file) {
		if (basename($file) == 'manifest.xml') {
			$modelName = $prefix.'\\'.str_replace('/', '\\', str_replace($folder, '', dirname($file)));
			if (in_array($modelName, ['Test\Load\Malformed', 'Test\Manifest_V_2', 'Test\Manifest_V_2\Inherited_V_2'])) {
				continue;
			}
			$obj = $model->loadObject($modelName);
			echo $obj;
			// $unit->saveObject($obj);
		}
	}
}
die();
*/
/*
$interfacer = new AssocArrayInterfacer();
$model = ModelManager::getInstance()->getInstanceModel('Comhon\Serialization\File');
echo $model->import(
	$interfacer->read('/home/jean-philippe/ReposGit/comhon/docker/assets/manifests/serialization/Sample/manifest.json'),
	$interfacer
);
die();
*/

