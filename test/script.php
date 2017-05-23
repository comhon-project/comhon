<?php

$lFolders = [
	'/home/jean-philippe/ReposGit/comhon/src/',
];

$lFiles = [];
$lFiles = getDirContents('/home/jean-philippe/ReposGit/comhon/src/', $lFiles);

$begin = '<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
';

foreach ($lFiles as $lFile) {
	var_dump($lFile);
	if (substr($lFile, -4) === '.php') {
		$content = file_get_contents($lFile);
		$newContent = str_replace('<?php', $begin, $content);
		file_put_contents($lFile, $newContent);
	}
}

function getDirContents($dir, &$results = array()){
	$files = scandir($dir);
	
	foreach($files as $key => $value){
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

