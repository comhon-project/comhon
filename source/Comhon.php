<?php

spl_autoload_register(function ($pClass) {
	$lPrefix = 'comhon\\';
	
	if (strncmp($lPrefix, $pClass, strlen($lPrefix)) !== 0) {
		return;
	}
	if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php')) {
		include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php';
	}
});
