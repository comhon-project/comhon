<?php

function getDirPhpFiles($dir, &$results = array()){
	$files = scandir($dir);
	
	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			if(substr($path, -4) === ('.php') && $path !== '/home/jean-philippe/ReposGit/comhon/test/DeletePrefixVariable.php') {
				$results[] = $path;
			}
		} else if($value != "." && $value != "..") {
			getDirPhpFiles($path, $results);
		}
	}
	
	return $results;
}
$i = 0;

foreach (getDirPhpFiles('/home/jean-philippe/ReposGit/comhon/') as $file) {
	$content = file_get_contents($file);
	
	$pos = 0;
	while (($pos = strpos($content, '$', $pos)) !== false) {
		if (ctype_lower($content[$pos+1]) && ctype_upper($content[$pos+2])) {
			var_dump('$'.$content[$pos+1].$content[$pos+2]);
		}
		$pos++;
	}
	
	/*$contentParts = explode(' function ', $content);
	
	if (count($contentParts) <= 1) {
		continue;
	}
	foreach ($contentParts as & $contentPart) {
		$originVariables = [];
		$newVariables = [];
		$pos = 0;
		
		while (($pos = strpos($contentPart, '$l', $pos)) !== false) {
			$pos += 2;
			if (ctype_upper($contentPart[$pos])) {
				$variable = '';
				while (ctype_alnum($contentPart[$pos])) {
					$variable .= $contentPart[$pos];
					$pos++;
				}
				if (!in_array('$l' . $variable, $originVariables)) {
					$originVariables[] = '$l' . $variable;
					if (!ctype_upper($variable[1])) {
						$variable[0] = strtolower($variable[0]);
					}
					if (strpos($contentPart, '$' . $variable) === false) {
						$newVariables[] = '$' . $variable;
					} else {
						array_pop($originVariables);
					}
				}
			}
		}
		$contentPart = str_replace($originVariables, $newVariables, $contentPart);
	}
	//var_dump(implode(' function ', $contentParts));
	file_put_contents($file, implode(' function ', $contentParts));*/
	/*
	$i++;
	if ($i > 1) {
		die();
	}*/
	
	/*$originVariables = [];
	$newVariables = [];
	$pos = 0;
	while (($pos = strpos($content, '$p', $pos)) !== false) {
		$pos += 2;
		if (ctype_upper($content[$pos])) {
			$variable = '';
			while (ctype_alnum($content[$pos])) {
				$variable .= $content[$pos];
				$pos++;
			}
			if (!in_array('$p' . $variable, $originVariables)) {
				$originVariables[] = '$p' . $variable;
				if (!ctype_upper($variable[1])) {
					$variable[0] = strtolower($variable[0]);
				}
				$newVariables[] = '$' . $variable;
			}
		}
	}
	
	file_put_contents($file, str_replace($originVariables, $newVariables, $content));*/
	
	/*$originVariables = [];
	$newVariables = [];
	$pos = 0;
	while (($pos = strpos($content, '$this->m', $pos)) !== false) {
		$pos += 8;
		if (ctype_upper($content[$pos])) {
			$variable = '';
			while (ctype_alnum($content[$pos])) {
				$variable .= $content[$pos];
				$pos++;
			}
			if (!in_array('$this->m' . $variable, $originVariables)) {
				$originVariables[] = '$this->m' . $variable;
				$originVariables[] = '$m' . $variable;
				$variable[0] = strtolower($variable[0]);
				$newVariables[] = '$this->' . $variable;
				$newVariables[] = '$' . $variable;
			}
		}
	}
	file_put_contents($file, str_replace($originVariables, $newVariables, $content));
	
	$content = file_get_contents($file);
	$originVariables = [];
	$newVariables = [];
	$pos = 0;
	while (($pos = strpos($content, '->m', $pos)) !== false) {
		$pos += 3;
		if (ctype_upper($content[$pos])) {
			$variable = '';
			while (ctype_alnum($content[$pos])) {
				$variable .= $content[$pos];
				$pos++;
			}
			if (!in_array('->m' . $variable, $originVariables)) {
				$originVariables[] = '->m' . $variable;
				$variable[0] = strtolower($variable[0]);
				$newVariables[] = '->' . $variable;
			}
		}
	}
	file_put_contents($file, str_replace($originVariables, $newVariables, $content));*/
}

