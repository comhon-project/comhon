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

use Comhon\Object\AbstractComhonObject;

class ArgumentException extends ComhonException {
	
	/**
	 * 
	 * @param mixed $argument
	 * @param string|array $expected
	 * @param integer $index start from 1
	 * @param array $enum specify it only if argument doesn't belong to an enumeration
	 */
	public function __construct($argument, $expected, $index, $enum = null) {
		if (is_object($argument)) {
			if ($argument instanceof AbstractComhonObject) {
				$type = $argument->getComhonClass();
			} else {
				$type = get_class($argument);
			}
		} else {
			$type = gettype($argument);
		}
		
		$nodes       = debug_backtrace();
		$definedFile = $nodes[0]['file'];
		$callLine    = $nodes[1]['line'];
		$callFile    = $nodes[1]['file'];
		
		$function = isset($nodes[1]['class']) 
			? $nodes[1]['class'].'::'.$nodes[1]['function']
			: $nodes[1]['function'];
		
		$expected = is_array($expected)
			? 'be one of '.json_encode($expected)
			: "be a $expected".(is_array($enum) ? (" that belong to ".json_encode($enum)) : '');
		
		$value = is_array($enum) && (is_string($argument) || is_int($argument) || is_float($argument)) ? " '$argument' " : ' ' ;
		
		$message = "Argument at index $index passed to $function() "
			."must $expected, $type{$value}given, called in {$callFile} on line {$callLine} "
			."and defined in {$definedFile}";
		
		parent::__construct($message);
	}
	
}