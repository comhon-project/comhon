<?php

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/ObjectManagerLib/source/');

require_once 'Comhon.php';

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
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'IntermediateVsComplexRequestTest.php';


// refactor serialization management

// mandatory value when serialize
// common models/values in unique files
// left/inner join simple/function litteral
// versionning get instance model 3rd parameter version
// inheritage with join table
