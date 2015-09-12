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

// including objectManager
require_once "objectManager/ObjectManager.php";
