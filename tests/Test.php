<?php

set_include_path(get_include_path().PATH_SEPARATOR.'/home/jean-philippe/ReposGit/ObjectManagerLib/source/');

require_once 'Comhon.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'RequestTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ExtendedModelTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ExtendedValueTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'XmlSerializationTest.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'list' . DIRECTORY_SEPARATOR . 'ImportExportTest.php';

// test complex request
// property ids not overridable manifest with same serialization (sure ?)
// replace composition by agregation
// mandatory value when serialize
// manage foreign property with several id

// versionning get instance model 3rd parameter version