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

echo "
   ____                _                 _ 
  / ___|___  _ __ ___ | |__   ___  _ __ | |
 | |   / _ \| '_ ` _ \| '_ \ / _ \| '_ \| |
 | |__| (_) | | | | | | | | | (_) | | | |_|
  \____\___/|_| |_| |_|_| |_|\___/|_| |_(_)
                                           \n\n";

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


?>
