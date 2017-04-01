<?php
namespace comhon\utils;

class Utils {
	
	/**
	 * merge arrays
	 * keep numeric keys even if all keys are numeric 
	 * (native function array_merge transform them to have a non assoc array (0,1,2,3...))
	 * @param array $pOrginalArray
	 * @param array $pArrayToMerge
	 * @return array
	 */
	public static function array_merge($pOrginalArray, $pArrayToMerge) {
		foreach ($pArrayToMerge as $lKey => $Value) {
			$pOrginalArray[$lKey] = $Value;
		}
		return $pOrginalArray;
	}
	
	/**
	 * 
	 * @param string $pDir
	 * @return boolean true if success
	 */
	public static function delTree($pDir) {
		$lFiles = array_diff(scandir($pDir), ['.','..']);
		foreach ($lFiles as $lFile) {
			(is_dir("$pDir/$lFile")) ? self::delTree("$pDir/$lFile") : unlink("$pDir/$lFile");
		}
		return rmdir($pDir);
	}
	
	/**
	 * print called function
	 */
	public static function printStack() {
        $lNodes = debug_backtrace();
        for ($i = 1; $i < count($lNodes); $i++) {
        	trigger_error("$i. ".basename($lNodes[$i]['file']) .' : ' .$lNodes[$i]['function'] .'(' .$lNodes[$i]['line'].')');
        }
    } 
    
}
