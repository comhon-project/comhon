<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$autoload_rf = 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $autoload_rf)) {
	require_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $autoload_rf;
} elseif (file_exists(dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . $autoload_rf)) {
	require_once dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . $autoload_rf;
}