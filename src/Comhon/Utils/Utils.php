<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

class Utils {
	
	/**
	 * merge arrays
	 * 
	 * keep numeric keys even if all keys are numeric 
	 * (native function array_merge transform them to have a non assoc array (0,1,2,3...))
	 * 
	 * @param array $orginalArray
	 * @param array $arrayToMerge
	 * @return array
	 */
	public static function array_merge($orginalArray, $arrayToMerge) {
		foreach ($arrayToMerge as $key => $Value) {
			$orginalArray[$key] = $Value;
		}
		return $orginalArray;
	}
	
	/**
	 * delete folder from filesystem
	 * 
	 * @param string $dir
	 * @return boolean true if success
	 */
	public static function delTree($dir) {
		$files = array_diff(scandir($dir), ['.','..']);
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}
	
	/**
	 * print called function
	 */
	public static function printStack() {
        $nodes = debug_backtrace();
        for ($i = 1; $i < count($nodes); $i++) {
        	trigger_error("$i. ".basename($nodes[$i]['file']) .' : ' .$nodes[$i]['function'] .'(' .$nodes[$i]['line'].')');
        }
    } 
    
}
