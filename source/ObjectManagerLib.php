<?php

spl_autoload_register(function ($pClass) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.php';
});
