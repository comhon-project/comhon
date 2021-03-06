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
	 * delete directory and its content from filesystem
	 * 
	 * @param string $dir
	 * @return boolean true if success
	 */
	public static function deleteDirectory($dir) {
		$contents = scandir($dir);
		if ($contents !== false) {
			$files = array_diff(scandir($dir), ['.','..']);
			foreach ($files as $file) {
				is_dir($dir . DIRECTORY_SEPARATOR . $file) 
					? self::deleteDirectory($dir . DIRECTORY_SEPARATOR. $file) 
					: unlink($dir . DIRECTORY_SEPARATOR. $file);
			}
		}
		return rmdir($dir);
	}
	
	/**
	 * copy directory
	 * 
	 * @param string $src source directory
	 * @param string $dst destination directory
	 */
	public static function copyDirectory($src, $dst) {
		$dir = opendir($src);
		if (!file_exists($dst)) {
			mkdir($dst, 0777, true);
		}
		while(($file = readdir($dir)) !== false) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
					self::copyDirectory($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				}
				else {
					copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
		closedir($dir);
	}
	
	/**
	 * scan directory recursively
	 * 
	 * @param string $dir
	 */
	public static function scanDirectory($dir) {
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::SELF_FIRST);
		$files = [];
		/**
		 * @var \SplFileInfo $object
		 */
		foreach($objects as $name => $object) {
			if ($object->getBasename() === '.' || $object->getBasename() === '..') {
				continue;
			}
			$files[] = $name;
		}
		
		return $files;
	}
	
	/**
	 * print light backtrace with only file, function and line informations
	 */
	public static function printStack() {
        $nodes = debug_backtrace();
        for ($i = 1; $i < count($nodes); $i++) {
        	var_dump("$i. ".basename($nodes[$i]['file']) .' : ' .$nodes[$i]['function'] .'(' .$nodes[$i]['line'].')');
        }
	}
	
	/**
	 * make provided string compliant with camel case
	 *
	 * @param string $string
	 * @return string
	 */
	public static function toCamelCase($string) {
		$string = preg_replace_callback(
			"|([_-][a-zA-Z])|",
			function ($matches) {return strtoupper(substr($matches[1], 1));},
			$string
		);
		return lcfirst($string);
	}
	
    /**
	 * make provided string compliant with pascal case
     *
     * @param string $string
     * @return string
     */
    public static function toPascalCase($string) {
    	return ucfirst(self::toCamelCase($string));
    }
    
    /**
	 * make provided string compliant with snake case
     *
     * @param string $string
     * @return string
     */
    public static function toSnakeCase($string) {
    	$string = preg_replace_callback(
    		"|(?:([A-Z])([A-Z])([^A-Z]))|",
    		function ($matches) {
    			$separator = $matches[3] == '_' || $matches[3] == '-' ? '' : '_';
    			return $matches[1] . strtolower($matches[2] . $separator . $matches[3]);
    		},
    		$string
    	);
    	$string = preg_replace_callback(
    		"|(?:([^A-Z])([A-Z]))|",
    		function ($matches) {
    			$separator = $matches[1] == '_' || $matches[1] == '-' ? '' : '_';
    			return strtolower($matches[1] . $separator . $matches[2]);
    		},
    		$string
    	);
    	
    	return lcfirst(strtolower(str_replace('-', '_', $string)));
    }
    
    /**
	 * make provided string compliant with kebab case
     *
     * @param string $string
     * @return string
     */
    public static function toKebabCase($string) {
    	return str_replace('_', '-', self::toSnakeCase($string));
    }
    
    /**
     * generate a uuid v4
     * 
     * @return string
     */
    public static function generateUuidV4() {
    	$data = random_bytes(16);
    	
    	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    	
    	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
}
