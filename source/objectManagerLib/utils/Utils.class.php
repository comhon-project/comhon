<?php
namespace objectManagerLib\utils;

class Utils{
	
	const FIRST = 1;
	const LAST = -1;
	
	/**
	 * split a string into two string
	 * @param string $pInputText text to split
	 * @param string $pNeedle segment text where the text will be split
	 * @param integer $pPosition nth position of $pNeedle
	 * - if $pPosition > 0 seach occruence will start at beginning of string
	 * - if $pPosition < 0 seach occruence will start at the end of string
	 * @return boolean|array
	 */
	public static function splitStringWithNeedle($pInputText, $pNeedle, $pPosition) {
		$lLengthNeedle = strlen($pNeedle);
		$lLengthCut = strlen($pInputText) - $lLengthNeedle;
		$lReturn = false;
		if (($pPosition < 0) && ($lLengthNeedle == 1) && ($pPosition != -1)) {
			$pPosition = substr_count($pInputText, $pNeedle) + $pPosition + 1;
		}
		if ($pPosition == 1) {
			$lPos = stripos($pInputText, $pNeedle);
			$lReturn = array(substr($pInputText, 0, $lPos), ($lLengthCut == $lPos) ? "" : substr($pInputText, $lPos + $lLengthNeedle));
		}else if ($pPosition == -1) {
			$lPos = strrpos($pInputText, $pNeedle);
			$lReturn = array(substr($pInputText, 0, $lPos), ($lLengthCut == $lPos) ? "" : substr($pInputText, $lPos + $lLengthNeedle));
		}else if ((is_int($pPosition) || ctype_digit($pPosition)) && ($pPosition != 0)) {
			$lAbsPosition = abs($pPosition);
			$lInputText = ($pPosition < 0) ? strrev($pInputText) : $pInputText;
			$lExplodedString = explode($pNeedle, $lInputText);
			if (count($lExplodedString) > $lAbsPosition) {
				$lPos = 0;
				for ($i = 0; $i < $lAbsPosition; $i++) {
					$lPos += strlen($lExplodedString[$i]) + $lLengthNeedle;
				}
				$lPos = ($pPosition < 0) ? strlen($lInputText) - $lPos : $lPos - $lLengthNeedle;
				$lReturn = array(substr($pInputText, 0, $lPos), ($lLengthCut == $lPos) ? "" : substr($pInputText, $lPos + $lLengthNeedle));
			}
		}
		return $lReturn;
	}
	
	/**
	 * merge arrays
	 * keep numeric keys even if all keys are numeric (array_merge transform them to have a non assoc array (0,1,2,3...))
	 * @param array $pOrginalArray
	 * @param array $pArrayToMerge
	 * @return array
	 */
	public static function array_merge_extended($pOrginalArray, $pArrayToMerge) {
		foreach ($pArrayToMerge as $lKey => $Value) {
			$pOrginalArray[$lKey] = $Value;
		}
		return $pOrginalArray;
	}
	
	/**
	 * print called function
	 */
	public static function printStack() {
        $lNodes = debug_backtrace();
        for ($i = 1; $i < count($lNodes); $i++) {
        	trigger_error("$i. ".basename($lNodes[$i]['file']) ." : " .$lNodes[$i]['function'] ."(" .$lNodes[$i]['line'].")");
        }
    } 
    
}
