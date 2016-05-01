<?php

spl_autoload_register(function ($pClass) {
	include_once '§TOKEN:installPath§' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $pClass) . '.class.php';
});
