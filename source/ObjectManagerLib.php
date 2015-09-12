<?php

$gInstallPath = "§TOKEN:installPath§";
if(!file_exists($gInstallPath) || !is_dir($gInstallPath)) {
	trigger_error("Include path '{$gInstallPath}' not exists", E_USER_WARNING);
}else {
	$lPaths = explode(PATH_SEPARATOR, get_include_path());
	if(array_search($gInstallPath, $lPaths) === false) {
		array_push($lPaths, $gInstallPath);
	}
	set_include_path(implode(PATH_SEPARATOR, $lPaths));
}

// including database controller
require_once "objectManager/database/DatabaseController.class.php";

// including utils
require_once 'objectManager/utils/Utils.class.php';

// including controllers
require_once 'objectManager/controller/Controller.class.php';
require_once 'objectManager/controller/ForeignObjectReplacer.class.php';
require_once 'objectManager/controller/ForeignObjectLoader.class.php';

// Including objects
require_once 'objectManager/object/controller/Controller.class.php';
require_once 'objectManager/object/controller/ConditionOptimizer.class.php';

require_once 'objectManager/object/object/Condition.class.php';
require_once 'objectManager/object/object/ConditionExtended.class.php';
require_once 'objectManager/object/object/LinkedConditions.class.php';

require_once 'objectManager/object/object/Object.class.php';
require_once 'objectManager/object/object/UnloadObject.class.php';

require_once 'objectManager/object/object/SerializationUnit.class.php';
require_once 'objectManager/object/object/SqlTable.class.php';
require_once 'objectManager/object/object/JsonFile.class.php';
require_once 'objectManager/object/object/JoinedTables.class.php';

require_once 'objectManager/object/singleton/InstanceModel.class.php';

require_once 'objectManager/object/model/Property.class.php';
require_once 'objectManager/object/model/SerializableProperty.class.php';

require_once 'objectManager/object/model/Model.class.php';
require_once 'objectManager/object/model/ModelContainer.class.php';
require_once 'objectManager/object/model/ModelArray.class.php';
require_once 'objectManager/object/model/ModelEnum.class.php';
require_once 'objectManager/object/model/ModelForeign.class.php';
require_once 'objectManager/object/model/SimpleModel.class.php';

require_once 'objectManager/object/model/Integer.class.php';
require_once 'objectManager/object/model/Float.class.php';
require_once 'objectManager/object/model/Boolean.class.php';
require_once 'objectManager/object/model/String.class.php';
