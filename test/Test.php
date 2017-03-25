<?php

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/ObjectManagerLib/source/');

require_once 'Comhon.php';

spl_autoload_register(function ($pClass) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php';
});

require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ExtendedValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'XmlSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'JsonSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ImportExportTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleForeignTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'ComplexRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'MultipleIdRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'RequestFailureTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateVsComplexRequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'PartialImportExportTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'DatabaseSerializationTest.php';

// add error code
// add simple model color, email
// same function for each kind of export
// remove $p $l
// psr
// add Php doc

// real unit test
// rapide load Unique model in modelManager
// from object specifying object path (object.property.property)
// mandatory value when serialize
// common models/values in unique files
// left/inner join simple/function litteral
// versionning get instance model 3rd parameter version
// inheritage with join table
// manifest validator