#!/usr/bin/php

<?php

// Getting all Installations Configurations
$lConfigurationsList = parse_ini_file("install.ini", true);
$lArgConf = null;
foreach ($argv as $arg) {
	if (strpos($arg, '--conf=') !== false) {
		$lArgConf = substr($arg, 7);
	}
}

switch ($lArgConf) {
	case "local":
		echo "local\n";
		$lConfigName = 'local';
	break;
	default:
		echo "Choose a configuration:\n";
		foreach($lConfigurationsList as $lProfile => $lValue) {
			echo " - $lProfile\n";
		}
		echo "Type a configuration name: ";
		flush();
		$lConfigName = trim(fgets(STDIN));
	break;
}

if(!isset($lConfigurationsList[$lConfigName])) {
	die("Unknow configuration.\n");
}

global $gConfiguration;
$gConfiguration = $lConfigurationsList[$lConfigName];

echo "Preparing Core...\n";
$lBuildPath = "build";
exec(sprintf("rm -rf %s", $lBuildPath));
mkdir($lBuildPath);


$lSourcePath = file_exists("tools")? "" : "../";
$lDirectory = "source";

echo "copying sources...\n";
exec(sprintf("cp -r %s%s %s", $lSourcePath, $lDirectory, $lBuildPath));

echo "Finalizing...\n";
recursiveReplace($lBuildPath);

echo "Synchronizing...\n";
$lBuildSourcePath = sprintf("%s/%s", $lBuildPath, $lDirectory);
$lHost = $gConfiguration["targetHost"];
$lUser = $gConfiguration["targetUser"];
$lPath = $gConfiguration["targetPath"];
exec(sprintf("rsync -avz --delete-after %s/ %s:%s", $lBuildSourcePath, $lHost, $lPath));

// delete build directory
echo "Cleanning...\n";
exec(sprintf("rm -rf %s", $lBuildPath));


die("Terminated.\n");

/***********************************************/
/*                 Fonctions                   */
/***********************************************/

function recursiveReplace($pPath) {
	if(is_dir($pPath)) {
		$lFiles = scandir($pPath);
		foreach($lFiles as $lFile) {
			$lPath = sprintf("%s/%s", $pPath, $lFile);
			if(is_dir($lPath)) {
				if(!in_array($lFile, array(".", ".."))) {
					recursiveReplace($lPath);
				}
			} elseif(is_file($lPath)) {
				replaceToken($lPath);
			}
		}
	}
}



function replaceToken($pFile) {
	$lContent = file_get_contents($pFile);
	$lPattern = "/§TOKEN:([A-Za-z0-9_]*)§/";
	$lContent = preg_replace_callback($lPattern, "replaceCallBack", $lContent);
	file_put_contents($pFile, $lContent);
}



function replaceCallBack($pToken) {
	$lReturn = "";
	global $gConfiguration;
	$lKey = substr($pToken[0], 8, -2);
	if(isset($gConfiguration[$lKey])) {
		$lReturn = $gConfiguration[$lKey];
	} 
	else {
		die("Fatal error: Unknow Token $lKey");
	}
	return $lReturn;
}


?>
