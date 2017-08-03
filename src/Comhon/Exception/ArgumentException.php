<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Exception;

use Comhon\Object\ComhonObject;

class ArgumentException extends ComhonException {
	
	/**
	 * 
	 * @param mixed $argument
	 * @param string|array $expected
	 * @param integer $index
	 */
	public function __construct($argument, $expected, $index) {
		if (is_object($argument)) {
			if ($argument instanceof ComhonObject) {
				$type = $argument->getComhonClass();
			} else {
				$type = get_class($argument);
			}
		} else {
			$type = gettype($argument);
		}
		
		$nodes       = debug_backtrace();
		$definedLine = $nodes[0]['line'];
		$definedFile = $nodes[0]['file'];
		$callLine    = $nodes[1]['line'];
		$callFile    = $nodes[1]['file'];
		
		$function = isset($nodes[1]['class']) 
			? $nodes[1]['class'].'::'.$nodes[1]['function']
			: $nodes[1]['function'];
		
		$expected = is_array($expected)
			? 'belong to enumeration '.json_encode($expected)
			: "be a $expected";
		
		$message = "Argument at index $index passed to $function() "
			."must $expected, $type given, called in {$callFile} on line {$callLine} "
			."and defined in {$definedFile}";
		
		parent::__construct($message);
	}
	
}